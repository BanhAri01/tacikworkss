<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $sales = OrderItem::query()
            ->where('seller_id', $user->id)
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid));

        return view('seller.dashboard', [
            'profile' => $user->sellerProfile,
            'salesCount' => (clone $sales)->count(),
            'netTotal' => (int) (clone $sales)->sum('seller_net_cents'),
            'netThisMonth' => (int) (clone $sales)->where('created_at', '>=', now()->startOfMonth())->sum('seller_net_cents'),
            'productsCount' => $user->products()->count(),
            'recent' => (clone $sales)->with('order:id,paid_at')->latest('id')->limit(10)->get(),
        ]);
    }
}
