<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Jobs\NotifyBuyersOfUpdate;
use App\Jobs\ProcessProductPreview;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveProduct
{
    /**
     * Create or update a seller's product. Editing an approved product sends it back to review.
     *
     * @param  array{
     *     title: string, category_id: int, description: string, is_free: bool,
     *     file_formats: array<int, string>, compatible_software: array<int, string>,
     *     tags: array<int, string>, prices: array<int, int>, version?: string,
     *     changelog?: ?string, remove_preview_ids?: array<int, int>, preview_order?: array<int, int>,
     * }  $data  prices = license_id => cents (ignored when free)
     * @param  array<int, UploadedFile>  $newPreviews
     */
    public function handle(User $seller, ?Product $product, array $data, array $newPreviews = [], ?UploadedFile $zip = null): Product
    {
        $disk = Storage::disk(config('marketplace.products_disk'));
        $storedPaths = [];
        $isUpdate = $zip && $product && $product->files()->exists();

        try {
            $product = DB::transaction(function () use ($seller, $product, $data, $newPreviews, $zip, &$storedPaths) {
                $product ??= new Product(['seller_id' => $seller->id, 'status' => ProductStatus::Draft]);

                $product->fill([
                    'title' => $data['title'],
                    'category_id' => $data['category_id'],
                    'description' => $data['description'],
                    'is_free' => $data['is_free'],
                    'file_formats' => array_values($data['file_formats']),
                    'compatible_software' => array_values($data['compatible_software']),
                ]);

                if (! $product->exists || $product->isDirty('title')) {
                    $product->slug = Product::uniqueSlug($data['title'], $product->id);
                }

                // Content or new images must be re-reviewed; price and file-version updates go live directly.
                if ($product->exists && $product->status === ProductStatus::Approved && ($product->isDirty() || $newPreviews)) {
                    $product->status = ProductStatus::Pending;
                    $product->submitted_at = now();
                }

                $product->save();
                $product->tags()->sync(Tag::idsFromNames($data['tags']));

                $this->syncPrices($product, $data['is_free'] ? [] : $data['prices']);

                $this->removePreviews($product, $data['remove_preview_ids'] ?? []);
                $this->reorderPreviews($product, $data['preview_order'] ?? []);

                $nextSort = (int) $product->previews()->max('sort') + 1;
                foreach ($newPreviews as $file) {
                    $path = $file->store('preview-sources/'.$product->id, ['disk' => config('marketplace.products_disk')]);
                    $storedPaths[] = $path;
                    $product->previews()->create(['source_path' => $path, 'sort' => $nextSort++]);
                }

                if ($zip) {
                    $path = $zip->store('files/'.$product->id, ['disk' => config('marketplace.products_disk')]);
                    $storedPaths[] = $path;
                    $version = $data['version'] ?? $product->version;

                    $product->files()->create([
                        'path' => $path,
                        'original_name' => Str::limit($zip->getClientOriginalName(), 200, ''),
                        'size' => $zip->getSize(),
                        'version' => $version,
                        'changelog' => $data['changelog'] ?? null,
                    ]);
                    $product->update(['version' => $version]);
                }

                $product->refreshMinPrice();

                return $product;
            });
        } catch (\Throwable $e) {
            $disk->delete($storedPaths);

            throw $e;
        }

        $product->previews()->whereNotNull('source_path')->pluck('id')
            ->each(fn (int $id) => ProcessProductPreview::dispatch($id)->afterCommit());

        // A replacement file is a new version: tell everyone who bought it.
        if ($isUpdate) {
            NotifyBuyersOfUpdate::dispatch($product->latestFile()->value('id'))->afterCommit();
        }

        return $product;
    }

    /** @param  array<int, int>  $prices license_id => cents */
    private function syncPrices(Product $product, array $prices): void
    {
        $prices = array_filter($prices, fn ($cents) => $cents !== null && $cents > 0);

        $product->licensePrices()->whereNotIn('license_id', array_keys($prices))->delete();

        foreach ($prices as $licenseId => $cents) {
            $product->licensePrices()->updateOrCreate(['license_id' => $licenseId], ['price_cents' => $cents]);
        }

        $product->unsetRelation('licensePrices');
    }

    /** @param  array<int, int>  $ids */
    private function removePreviews(Product $product, array $ids): void
    {
        $product->previews()->whereKey($ids)->get()->each(function ($preview) {
            Storage::disk('public')->delete(array_filter([$preview->path, $preview->thumb_path]));
            if ($preview->source_path) {
                Storage::disk(config('marketplace.products_disk'))->delete($preview->source_path);
            }
            $preview->delete();
        });
    }

    /** @param  array<int, int>  $orderedIds */
    private function reorderPreviews(Product $product, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $sort => $id) {
            $product->previews()->whereKey($id)->update(['sort' => $sort]);
        }
    }
}
