<?php

namespace App\Jobs;

use App\Models\ProductPreview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

/**
 * Converts an uploaded preview source into a WebP thumbnail and a watermarked large WebP on the public disk.
 */
class ProcessProductPreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $previewId) {}

    public function handle(): void
    {
        $preview = ProductPreview::find($this->previewId);

        if (! $preview?->source_path) {
            return;
        }

        $sourceDisk = Storage::disk(config('marketplace.products_disk'));
        $binary = $sourceDisk->get($preview->source_path);
        $config = config('marketplace.previews');
        $manager = new ImageManager(new Driver);
        $base = 'previews/'.$preview->product_id.'/'.Str::uuid();

        $thumb = $manager->read($binary)->scaleDown(width: $config['thumb_width']);
        Storage::disk('public')->put($base.'-thumb.webp', (string) $thumb->toWebp($config['quality']));

        $large = $manager->read($binary)->scaleDown(width: $config['large_width']);
        $this->watermark($large, $config['watermark_font']);
        Storage::disk('public')->put($base.'.webp', (string) $large->toWebp($config['quality']));

        $old = array_filter([$preview->path, $preview->thumb_path]);
        $sourcePath = $preview->source_path;

        $preview->update([
            'path' => $base.'.webp',
            'thumb_path' => $base.'-thumb.webp',
            'source_path' => null,
        ]);

        Storage::disk('public')->delete($old);
        $sourceDisk->delete($sourcePath);
    }

    /** Diagonal tiled text so the watermark can't simply be cropped out. */
    private function watermark(ImageInterface $image, string $font): void
    {
        $text = config('app.name');
        $size = max(18, (int) round($image->width() / 22));
        $stepX = $size * 9;
        $stepY = $size * 5;

        for ($y = $stepY / 2, $row = 0; $y < $image->height(); $y += $stepY, $row++) {
            for ($x = ($row % 2) * $stepX / 2; $x < $image->width() + $stepX; $x += $stepX) {
                foreach ([[1, 'rgba(0, 0, 0, 0.18)'], [0, 'rgba(255, 255, 255, 0.32)']] as [$offset, $color]) {
                    $image->text($text, (int) $x + $offset, (int) $y + $offset, function (FontFactory $f) use ($font, $size, $color) {
                        $f->filename($font);
                        $f->size($size);
                        $f->color($color);
                        $f->align('center');
                        $f->valign('middle');
                        $f->angle(-25);
                    });
                }
            }
        }
    }
}
