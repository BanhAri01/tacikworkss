<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Reports\MonthlyTotals;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SalesController extends Controller
{
    public function index(Request $request, MonthlyTotals $monthly)
    {
        $month = $this->month($request);
        $sales = $this->query($request, $month);

        return view('seller.sales.index', [
            'items' => (clone $sales)->with('order:id,uuid,paid_at')->latest('order_items.id')->paginate(25)->withQueryString(),
            'totals' => (clone $sales)->selectRaw('COUNT(*) as n, SUM(price_cents) as price, SUM(buyer_fee_cents) as buyer_fee, SUM(payment_fee_cents) as payment_fee, SUM(platform_fee_cents) as platform_fee, SUM(seller_net_cents) as net')->first(),
            'chart' => $monthly->handle($this->query($request, null), 'seller_net_cents', 'created_at'),
            'month' => $month?->format('Y-m'),
        ]);
    }

    public function export(Request $request)
    {
        $sales = $this->query($request, $this->month($request))->with('order:id,uuid,paid_at')->oldest('order_items.id');

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'order', 'item', 'license', 'traffic', 'price', 'discount', 'buyer_fee', 'paypal_fee', 'platform_fee', 'fee_rate', 'net', 'refunded']);

            $sales->chunk(500, function ($items) use ($out) {
                foreach ($items as $item) {
                    fputcsv($out, [
                        $item->order->paid_at?->toDateTimeString(), $item->order->uuid, $item->product_title, $item->license_name,
                        $item->attribution->value, Money::toDecimal($item->price_cents), Money::toDecimal($item->discount_cents),
                        Money::toDecimal($item->buyer_fee_cents), Money::toDecimal($item->payment_fee_cents),
                        Money::toDecimal($item->platform_fee_cents), Money::percent($item->fee_bps),
                        Money::toDecimal($item->seller_net_cents), $item->refunded_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, 'sales-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request, ?Carbon $month): Builder
    {
        return OrderItem::query()
            ->where('order_items.seller_id', $request->user()->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', [OrderStatus::Paid, OrderStatus::Refunded]))
            ->when($month, fn ($q) => $q->whereBetween('order_items.created_at', [$month, $month->copy()->endOfMonth()]));
    }

    private function month(Request $request): ?Carbon
    {
        $value = (string) $request->query('month');

        return preg_match('/^\d{4}-\d{2}$/', $value) ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : null;
    }
}
