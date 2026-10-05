<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /** Form field => [setting key, kind]. Percentages are entered like "10" or "4.4" and stored as basis points. */
    private const FIELDS = [
        'fee_own' => ['fee_own_bps', 'percent'],
        'fee_marketplace' => ['fee_marketplace_bps', 'percent'],
        'processing_fee' => ['processing_fee_bps', 'percent'],
        'processing_fee_fixed' => ['processing_fee_fixed_cents', 'money'],
        'affiliate' => ['affiliate_bps', 'percent'],
        'affiliate_payout_min' => ['affiliate_payout_min_cents', 'money'],
    ];

    public function edit()
    {
        $values = collect(self::FIELDS)->map(fn ($f) => Money::toDecimal(Setting::int($f[0])))->all();

        return view('admin.settings', [
            'values' => $values,
            'freeProducts' => Product::published()->where('is_free', true)->orderBy('title')->get(['id', 'title', 'seller_id', 'status']),
            'freeOfTheWeek' => Setting::get('free_of_the_week_product_id'),
        ]);
    }

    public function update(Request $request)
    {
        $rules = collect(self::FIELDS)->map(fn ($f) => ['required', 'regex:'.Money::DECIMAL_REGEX])->all();
        $rules['free_of_the_week_product_id'] = ['nullable', 'integer', Rule::exists('products', 'id')->where('is_free', true)];

        $data = $request->validate($rules);

        foreach (self::FIELDS as $field => [$key, $kind]) {
            $value = Money::toCents($data[$field]); // "4.4" → 440 bps, "0.30" → 30 cents

            if ($kind === 'percent' && $value > 9000) {
                return back()->withErrors([$field => __('Percentages must be 90 or less.')])->withInput();
            }

            Setting::put($key, $value);
        }

        Setting::put('free_of_the_week_product_id', $data['free_of_the_week_product_id'] ? (int) $data['free_of_the_week_product_id'] : null);

        return back()->with('status', __('Settings saved.'));
    }
}
