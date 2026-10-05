<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Reports\MonthlyTotals;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Report;
use App\Models\SellerProfile;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke(MonthlyTotals $monthly)
    {
        $paidItems = fn () => OrderItem::query()
            ->whereNull('refunded_at')
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid));

        $last30 = now()->subDays(30);

        return view('admin.dashboard', [
            'gmv30' => (int) Order::where('status', OrderStatus::Paid)->where('paid_at', '>=', $last30)->sum('total_cents'),
            'revenue30' => (int) $paidItems()->where('created_at', '>=', $last30)->sum('platform_fee_cents')
                - (int) $paidItems()->where('created_at', '>=', $last30)->sum('affiliate_commission_cents'),
            'orders30' => Order::where('status', OrderStatus::Paid)->where('paid_at', '>=', $last30)->count(),
            'newUsers30' => User::where('created_at', '>=', $last30)->count(),
            'usersCount' => User::count(),
            'sellersCount' => SellerProfile::where('status', SellerStatus::Approved)->count(),
            'pendingSellers' => SellerProfile::where('status', SellerStatus::Pending)->count(),
            'pendingProducts' => Product::where('status', ProductStatus::Pending)->count(),
            'openReports' => Report::where('status', 'open')->count(),
            'chart' => $monthly->handle($paidItems(), 'platform_fee_cents - affiliate_commission_cents', 'created_at'),
        ]);
    }
}
