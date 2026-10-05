<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\RefundOrderItem;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\PaymentGatewayException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $orders = Order::query()
            ->with('buyer:id,name,email')
            ->withCount('items')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('uuid', $term)
                ->orWhere('gateway_ref', $term)
                ->orWhereRelation('buyer', 'email', 'like', "%{$term}%")
                ->orWhereRelation('items', 'purchase_code', $term)))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load(['buyer', 'items.seller.sellerProfile', 'items.affiliate:id,name'])]);
    }

    public function refund(Order $order, OrderItem $item, RefundOrderItem $refund)
    {
        abort_unless($item->order_id === $order->id, 404);

        try {
            $refund->handle($item);
        } catch (PaymentGatewayException) {
            return back()->withErrors(['refund' => __('PayPal refused the refund. Check the PayPal dashboard and the log.')]);
        }

        return back()->with('status', __('Refunded ":item".', ['item' => $item->product_title]));
    }
}
