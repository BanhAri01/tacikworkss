<?php

namespace App\Actions\Seller;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/** Crops an avatar/banner to size and stores it as WebP on the public disk. */
class StoreShopImage
{
    public function handle(UploadedFile $file, ?string $oldPath, int $width, int $height): string
    {
        $image = (new ImageManager(new Driver))->read($file->getRealPath())->cover($width, $height);
        $path = 'shops/'.Str::uuid().'.webp';

        Storage::disk('public')->put($path, (string) $image->toWebp(85));

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }
}
