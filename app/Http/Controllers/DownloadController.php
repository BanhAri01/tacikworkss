<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Download;
use App\Models\OrderItem;
use App\Models\ProductFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        $items = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $request->user()->id)->where('status', OrderStatus::Paid))
            ->with(['order:id,uuid,paid_at', 'product' => fn ($q) => $q->with(['cover', 'latestFile'])])
            ->latest('id')
            ->paginate(20);

        return view('downloads.index', compact('items'));
    }

    /** Logs the download and hands out a short-lived link to the latest file version. */
    public function file(Request $request, OrderItem $orderItem)
    {
        Gate::authorize('download', $orderItem);

        $file = $orderItem->product?->latestFile;
        abort_unless($file, 404, __('The seller hasn\'t uploaded a file yet.'));

        Download::create([
            'order_item_id' => $orderItem->id,
            'user_id' => $request->user()->id,
            'product_file_id' => $file->id,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        $minutes = config('marketplace.download_link_minutes');
        $disk = Storage::disk(config('marketplace.products_disk'));

        if (config('filesystems.disks.'.config('marketplace.products_disk').'.driver') === 's3') {
            return redirect()->away($disk->temporaryUrl($file->path, now()->addMinutes($minutes), [
                'ResponseContentDisposition' => 'attachment; filename="'.addslashes($file->original_name).'"',
            ]));
        }

        return redirect(URL::temporarySignedRoute('downloads.stream', now()->addMinutes($minutes), [
            'orderItem' => $orderItem->id,
            'file' => $file->id,
        ]));
    }

    /** Local-disk delivery behind a signed, expiring URL. */
    public function stream(Request $request, OrderItem $orderItem, ProductFile $file)
    {
        Gate::authorize('download', $orderItem);
        abort_unless($file->product_id === $orderItem->product_id, 404);

        return Storage::disk(config('marketplace.products_disk'))->download($file->path, $file->original_name);
    }

    public function license(OrderItem $orderItem)
    {
        Gate::authorize('download', $orderItem);

        $orderItem->load(['order.buyer', 'license', 'seller.sellerProfile']);

        return Pdf::loadView('downloads.license', ['item' => $orderItem])
            ->setPaper('a4')
            ->download('license-'.$orderItem->purchase_code.'.pdf');
    }
}
