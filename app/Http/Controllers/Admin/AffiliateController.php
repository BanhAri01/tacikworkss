<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;

/** Affiliates are paid manually by the platform (from its own PayPal), then marked paid here. */
class AffiliateController extends Controller
{
    public function index()
    {
        $balances = $this->unpaid()
            ->selectRaw('affiliate_id, SUM(affiliate_commission_cents) as total, COUNT(*) as sales')
            ->groupBy('affiliate_id')
            ->orderByDesc('total')
            ->get();

        $users = User::whereIn('id', $balances->pluck('affiliate_id'))->get(['id', 'name', 'email', 'affiliate_paypal_email'])->keyBy('id');

        return view('admin.affiliates.index', [
            'balances' => $balances,
            'users' => $users,
            'minimum' => Setting::int('affiliate_payout_min_cents'),
        ]);
    }

    public function markPaid(User $user)
    {
        $count = $this->unpaid()->where('affiliate_id', $user->id)->update(['affiliate_paid_at' => now()]);

        return back()->with('status', __('Marked :n commissions as paid for :name.', ['n' => $count, 'name' => $user->name]));
    }

    private function unpaid()
    {
        return OrderItem::query()
            ->whereNotNull('affiliate_id')
            ->whereNull('affiliate_paid_at')
            ->whereNull('refunded_at')
            ->where('affiliate_commission_cents', '>', 0)
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid));
    }
}
