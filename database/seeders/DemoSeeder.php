<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\TrafficSource;
use App\Enums\UserRole;
use App\Jobs\ProcessProductPreview;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\LedgerEntry;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Support\Fees;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use ZipArchive;

/**
 * Realistic demo marketplace for local development. All accounts use the password "password".
 * Run with: php artisan migrate:fresh --seed
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /** [username, display name, country, bio, preview colour] */
    private const SELLERS = [
        ['rawsastudio', 'Rawsa Studio', 'ID', 'Minimal branding kits and logo templates from Bandung.', '#1e3a5f'],
        ['kopikreatif', 'Kopi Kreatif', 'ID', 'Social media templates for cafés, small brands and creators.', '#6b3e26'],
        ['batikpixel', 'Batik Pixel', 'ID', 'Patterns and illustrations inspired by Indonesian textiles.', '#7c2d12'],
        ['nusantype', 'Nusantype Foundry', 'ID', 'Independent type foundry. Display and script fonts.', '#18181b'],
        ['lautmockup', 'Laut Mockup Co.', 'ID', 'Photorealistic mockups shot in our Bali studio.', '#0e7490'],
        ['pixelharbor', 'Pixel Harbor', 'MY', 'UI kits and dashboards for product teams.', '#4338ca'],
    ];

    /**
     * seller index => [title, category slug, formats, software, prices [personal, commercial, extended] in cents | 'free', tags]
     */
    private const PRODUCTS = [
        0 => [
            ['Minimal Logo Kit Vol. 1', 'templates-logos', ['AI', 'EPS', 'SVG'], ['Illustrator'], [1200, 2900, 9900], ['logo', 'minimal', 'branding']],
            ['Brand Guidelines Template', 'templates-print', ['INDD', 'PDF'], ['InDesign'], [1900, 3900, 12900], ['brand', 'guidelines', 'print']],
            ['Monoline Badge Collection', 'templates-logos', ['AI', 'SVG', 'PNG'], ['Illustrator', 'Figma'], [900, 2400, 7900], ['badge', 'vintage', 'logo']],
            ['Stationery Mockup Free Sample', 'mockups-branding', ['PSD'], ['Photoshop'], 'free', ['stationery', 'mockup', 'free']],
        ],
        1 => [
            ['Coffee Shop Instagram Pack', 'templates-social-media', ['PSD', 'FIG'], ['Photoshop', 'Figma', 'Canva'], [1500, 3500, 8900], ['instagram', 'coffee', 'social media']],
            ['Ramadan Social Media Kit', 'templates-social-media', ['PSD', 'FIG'], ['Photoshop', 'Canva'], [1200, 2900, 7900], ['ramadan', 'social media', 'islamic']],
            ['Creator Story Templates', 'templates-social-media', ['FIG', 'PNG'], ['Figma', 'Canva'], [900, 1900, 5900], ['stories', 'creator', 'instagram']],
            ['Pitch Deck: Startup Pro', 'templates-presentations', ['PPTX', 'KEY'], ['PowerPoint', 'Keynote', 'Google Slides'], [1900, 4900, 14900], ['pitch deck', 'presentation', 'startup']],
        ],
        2 => [
            ['Kawung Seamless Patterns', 'graphics-patterns', ['AI', 'SVG', 'PNG'], ['Illustrator'], [1100, 2600, 8900], ['batik', 'pattern', 'seamless']],
            ['Tropical Flora Illustrations', 'graphics-illustrations', ['AI', 'PNG', 'PROCREATE'], ['Illustrator', 'Procreate'], [1600, 3600, 9900], ['tropical', 'botanical', 'illustration']],
            ['Wayang Character Set', 'graphics-illustrations', ['AI', 'SVG'], ['Illustrator'], [2200, 4900, 13900], ['wayang', 'character', 'culture']],
            ['Mega Mendung Pattern Free', 'graphics-patterns', ['PNG', 'SVG'], ['Illustrator', 'Canva'], 'free', ['batik', 'pattern', 'free']],
        ],
        3 => [
            ['Gunung Display Font', 'fonts-display', ['OTF', 'TTF', 'WOFF'], [], [1500, 3900, 11900], ['display font', 'bold', 'headline']],
            ['Senja Script Font', 'fonts-script', ['OTF', 'TTF'], [], [1200, 2900, 8900], ['script', 'handwritten', 'wedding']],
            ['Kota Sans Family', 'fonts-sans-serif', ['OTF', 'TTF', 'WOFF'], [], [2900, 5900, 17900], ['sans serif', 'family', 'ui']],
            ['Rimba Serif', 'fonts-serif', ['OTF', 'TTF'], [], [1800, 3900, 11900], ['serif', 'editorial', 'elegant']],
        ],
        4 => [
            ['T-Shirt Mockup Bundle', 'mockups-apparel', ['PSD'], ['Photoshop'], [1400, 3200, 8900], ['t-shirt', 'apparel', 'mockup']],
            ['iPhone 17 Clay Mockups', 'mockups-devices', ['PSD', 'FIG'], ['Photoshop', 'Figma'], [1200, 2900, 7900], ['iphone', 'device', 'mockup']],
            ['Coffee Cup Packaging Mockup', 'mockups-packaging', ['PSD'], ['Photoshop', 'Affinity Photo'], [1000, 2500, 6900], ['packaging', 'coffee', 'mockup']],
            ['Business Card Mockup', 'mockups-branding', ['PSD'], ['Photoshop'], [800, 1900, 5900], ['business card', 'branding', 'mockup']],
        ],
        5 => [
            ['SaaS Dashboard UI Kit', 'ui-kits-dashboards', ['FIG', 'SKETCH'], ['Figma', 'Sketch'], [2900, 6900, 19900], ['dashboard', 'saas', 'ui kit']],
            ['Fintech Mobile App UI', 'ui-kits-mobile', ['FIG'], ['Figma'], [2400, 5900, 15900], ['fintech', 'mobile', 'app']],
            ['Landing Page Blocks', 'ui-kits-web', ['FIG', 'XD'], ['Figma', 'Adobe XD'], [1900, 4500, 12900], ['landing page', 'web', 'components']],
            ['Icon Set: 500 Line Icons', 'graphics-icons', ['SVG', 'FIG', 'AI'], ['Figma', 'Illustrator', 'Sketch'], [1500, 2900, 7900], ['icons', 'line', 'ui']],
        ],
    ];

    private ImageManager $images;

    private Collection $licenses;

    public function run(): void
    {
        // Seeding touches relations on many models; strict lazy-loading checks are for app code.
        Model::preventLazyLoading(false);
        fake()->seed(2026);

        // migrate:fresh empties the DB but not storage: drop files left by a previous seed.
        Storage::disk('public')->deleteDirectory('previews');
        Storage::disk('public')->deleteDirectory('shops');
        Storage::disk(config('marketplace.products_disk'))->deleteDirectory('files');
        Storage::disk(config('marketplace.products_disk'))->deleteDirectory('preview-sources');

        $this->images = new ImageManager(new Driver);
        $this->licenses = License::orderBy('sort')->get();

        $this->command?->info('Creating users…');
        [$sellers, $buyers, $affiliate] = $this->users();

        $this->command?->info('Creating products with preview images (takes ~1 minute)…');
        $products = $this->products($sellers);

        $this->command?->info('Creating orders, reviews and community data…');
        $this->extras($sellers, $products);
        $this->orders($buyers, $products, $affiliate);
        $this->community($buyers, $sellers, $products);

        $this->command?->table(['Role', 'Email', 'Password'], [
            ['Admin', config('marketplace.admin.email'), config('marketplace.admin.password')],
            ['Seller (PayPal connected)', 'rawsastudio@tacikworks.test', self::PASSWORD],
            ['Seller (not connected)', 'pixelharbor@tacikworks.test', self::PASSWORD],
            ['Seller applicant (pending)', 'applicant@tacikworks.test', self::PASSWORD],
            ['Buyer', 'buyer@tacikworks.test', self::PASSWORD],
            ['Affiliate', 'affiliate@tacikworks.test', self::PASSWORD],
            ['Suspended user', 'suspended@tacikworks.test', self::PASSWORD],
        ]);
    }

    /** @return array{0: Collection<int, User>, 1: Collection<int, User>, 2: User} */
    private function users(): array
    {
        $sellers = collect(self::SELLERS)->map(function (array $s, int $i) {
            $user = $this->user($s[1], $s[0].'@tacikworks.test', [UserRole::Buyer, UserRole::Seller]);

            SellerProfile::create([
                'user_id' => $user->id,
                'username' => $s[0],
                'display_name' => $s[1],
                'bio' => $s[3],
                'country' => $s[2],
                'paypal_email' => $s[0].'@tacikworks.test',
                'portfolio_url' => 'https://www.behance.net/'.$s[0],
                'status' => SellerStatus::Approved,
                'approved_at' => now()->subMonths(13),
                // The last seller hasn't connected PayPal yet, to show that state.
                'paypal_merchant_id' => $i < 5 ? 'DEMO'.strtoupper(Str::random(9)) : null,
                'paypal_payments_receivable' => $i < 5,
                'paypal_email_confirmed' => $i < 5,
                'paypal_connected_at' => $i < 5 ? now()->subMonths(12) : null,
                'tax_info' => $i === 0 ? [
                    'entity_type' => 'individual', 'legal_name' => 'Rawsa Pratama', 'country' => 'ID', 'tax_id' => '09.254.294.3-407.000',
                    'address' => 'Jl. Braga No. 12', 'city' => 'Bandung', 'postal_code' => '40111', 'is_us_person' => false,
                    'treaty_country' => 'ID', 'treaty_article' => '12', 'treaty_rate' => '10%', 'signature' => 'Rawsa Pratama',
                    'signed_at' => now()->subMonths(11)->toIso8601String(),
                ] : null,
            ]);

            return $user;
        });

        // A pending applicant and a suspended seller for the admin queues.
        $applicant = $this->user('Dewi Lestari', 'applicant@tacikworks.test', [UserRole::Buyer]);
        SellerProfile::create([
            'user_id' => $applicant->id, 'username' => 'dewilestari', 'display_name' => 'Dewi Lestari Design', 'country' => 'ID',
            'paypal_email' => 'applicant@tacikworks.test', 'portfolio_url' => 'https://dribbble.com/dewilestari',
            'bio' => 'Illustrator from Yogyakarta, 6 years of freelance work.', 'status' => SellerStatus::Pending,
        ]);

        $suspended = $this->user('Copy Cat', 'suspended@tacikworks.test', [UserRole::Buyer, UserRole::Seller]);
        $suspended->forceFill(['suspended_at' => now()->subWeek(), 'suspension_reason' => 'Uploaded stolen artwork'])->save();
        SellerProfile::create([
            'user_id' => $suspended->id, 'username' => 'copycat', 'display_name' => 'Copy Cat', 'country' => 'US',
            'paypal_email' => 'suspended@tacikworks.test', 'status' => SellerStatus::Suspended, 'approved_at' => now()->subMonths(2),
        ]);

        $buyers = collect([$this->user('Budi Santoso', 'buyer@tacikworks.test', [UserRole::Buyer])]);
        foreach (range(1, 24) as $i) {
            $buyers->push($this->user(fake()->name(), "buyer{$i}@tacikworks.test", [UserRole::Buyer], now()->subDays(random_int(5, 360))));
        }

        $affiliate = $this->user('Ayu Affiliate', 'affiliate@tacikworks.test', [UserRole::Buyer]);
        $affiliate->forceFill(['affiliate_code' => 'ayu2026', 'affiliate_paypal_email' => 'ayu.paypal@tacikworks.test'])->save();

        // An unverified account, to try the email verification flow.
        User::forceCreate(['name' => 'New Visitor', 'email' => 'unverified@tacikworks.test', 'password' => self::PASSWORD])->assignRole(UserRole::Buyer);

        return [$sellers, $buyers, $affiliate];
    }

    private function user(string $name, string $email, array $roles, ?Carbon $joined = null): User
    {
        $user = User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => self::PASSWORD,
            'email_verified_at' => $joined ?? now()->subYear(),
            'created_at' => $joined ?? now()->subYear(),
        ]);

        return $user->assignRole($roles);
    }

    /** @return Collection<int, Product> */
    private function products(Collection $sellers): Collection
    {
        $categories = Category::pluck('id', 'slug');
        $products = collect();

        foreach (self::PRODUCTS as $sellerIndex => $items) {
            $seller = $sellers[$sellerIndex];
            $color = self::SELLERS[$sellerIndex][4];

            foreach ($items as $i => [$title, $categorySlug, $formats, $software, $prices, $tags]) {
                $isFree = $prices === 'free';
                $published = now()->subDays(random_int(20, 380));

                $product = Product::forceCreate([
                    'seller_id' => $seller->id,
                    'category_id' => $categories[$categorySlug],
                    'title' => $title,
                    'slug' => Str::slug($title),
                    'description' => $this->description($title, $formats, $software),
                    'status' => ProductStatus::Approved,
                    'file_formats' => $formats,
                    'compatible_software' => $software,
                    'is_free' => $isFree,
                    'featured_at' => $i === 0 ? now()->subDays($sellerIndex) : null,
                    'version' => '1.'.random_int(0, 4),
                    'min_price_cents' => $isFree ? 0 : $prices[0],
                    'submitted_at' => $published->copy()->subDay(),
                    'published_at' => $published,
                    'created_at' => $published->copy()->subDays(2),
                ]);

                if (! $isFree) {
                    foreach ($this->licenses as $l => $license) {
                        $product->licensePrices()->create(['license_id' => $license->id, 'price_cents' => $prices[$l]]);
                    }
                }

                $product->tags()->sync(Tag::idsFromNames($tags));
                $this->previews($product, $color, $categorySlug);
                $this->zip($product);

                $products->push($product);
            }
        }

        $this->moderationExamples($sellers[0], $sellers[1], $categories);

        return $products->each->load(['licensePrices', 'seller.sellerProfile']);
    }

    /** One item in each non-live state so every admin/seller screen has something to show. */
    private function moderationExamples(User $sellerA, User $sellerB, $categories): void
    {
        $states = [
            [$sellerA, 'Geometric Logo Marks (in review)', ProductStatus::Pending, null],
            [$sellerB, 'Food Menu Template (rejected)', ProductStatus::Rejected, 'Preview images are too small and blurry. Please upload at least 1600px wide previews.'],
            [$sellerA, 'Watercolor Textures (draft)', ProductStatus::Draft, null],
        ];

        foreach ($states as [$seller, $title, $status, $reason]) {
            $product = Product::forceCreate([
                'seller_id' => $seller->id, 'category_id' => $categories['templates-print'], 'title' => $title, 'slug' => Str::slug($title),
                'description' => $this->description($title, ['PSD'], ['Photoshop']), 'status' => $status, 'rejection_reason' => $reason,
                'file_formats' => ['PSD'], 'compatible_software' => ['Photoshop'], 'min_price_cents' => 1500,
                'submitted_at' => $status === ProductStatus::Draft ? null : now()->subDays(2),
            ]);
            $product->licensePrices()->create(['license_id' => $this->licenses[0]->id, 'price_cents' => 1500]);
            $this->previews($product, '#52525b', 'templates-print', 1);
            $this->zip($product);
        }
    }

    /** Generates simple branded preview images and runs them through the real WebP/watermark job. */
    private function previews(Product $product, string $color, string $category, int $count = 3): void
    {
        $label = Str::headline(Str::after($category, '-'));
        $accents = ['#f59e0b', '#ffffff', '#22d3ee'];

        for ($n = 0; $n < $count; $n++) {
            $image = $this->images->create(1600, 1200)->fill($color);
            $image->drawCircle(1250 - $n * 200, 300 + $n * 150, function ($circle) use ($accents, $n) {
                $circle->radius(320);
                $circle->background($accents[$n % 3].'33');
            });
            $image->drawRectangle(120, 760, function ($rect) {
                $rect->size(1360, 6);
                $rect->background('rgba(255, 255, 255, 0.6)');
            });
            $image->text($product->title, 120, 700, function ($font) {
                $font->filename(config('marketplace.previews.watermark_font'));
                $font->size(84);
                $font->color('#ffffff');
                $font->wrap(1300);
            });
            $image->text(strtoupper($label).'  ·  '.($n + 1).'/'.$count, 120, 840, function ($font) {
                $font->filename(config('marketplace.previews.watermark_font'));
                $font->size(36);
                $font->color('rgba(255, 255, 255, 0.75)');
            });

            $path = 'preview-sources/'.$product->id.'/seed-'.$n.'.jpg';
            Storage::disk(config('marketplace.products_disk'))->put($path, (string) $image->toJpeg(85));

            $preview = $product->previews()->create(['source_path' => $path, 'sort' => $n]);
            (new ProcessProductPreview($preview->id))->handle();
        }
    }

    private function zip(Product $product): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dm');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('README.txt', "{$product->title}\n\nDemo file generated by DemoSeeder.\nLicense terms: see your license certificate.\n");
        $zip->addFromString('license.txt', 'Use according to the license you purchased on '.config('app.name').'.');
        $zip->close();

        $path = 'files/'.$product->id.'/'.Str::slug($product->title).'.zip';
        Storage::disk(config('marketplace.products_disk'))->put($path, file_get_contents($tmp));

        $product->files()->create([
            'path' => $path,
            'original_name' => Str::slug($product->title).'.zip',
            'size' => filesize($tmp),
            'version' => $product->version ?? '1.0',
            'changelog' => 'Initial release',
        ]);
        @unlink($tmp);
    }

    private function description(string $title, array $formats, array $software): string
    {
        $paragraph = fake()->paragraph(4);

        return "{$title} is a ready-to-use design asset made for designers, agencies and small brands.\n\n{$paragraph}\n\n"
            ."What's included:\n• Organised, layered source files\n• Free updates for life\n• Help file with font links\n\n"
            .'Formats: '.implode(', ', $formats).($software ? "\nWorks with: ".implode(', ', $software) : '');
    }

    /** Coupons, bundles, settings. */
    private function extras(Collection $sellers, Collection $products): void
    {
        Coupon::create(['seller_id' => $sellers[0]->id, 'code' => 'RAWSA20', 'type' => CouponType::Percent, 'value' => 20, 'max_uses' => 100]);
        Coupon::create(['seller_id' => $sellers[1]->id, 'code' => 'KOPI5', 'type' => CouponType::Fixed, 'value' => 500]);
        Coupon::create(['seller_id' => $sellers[3]->id, 'code' => 'TYPE50', 'type' => CouponType::Percent, 'value' => 50, 'expires_at' => now()->addMonth()]);

        $bundleSpecs = [
            [$sellers[0], 'Complete Branding Bundle', [0, 1, 2], 3900],
            [$sellers[3], 'Nusantype Font Library', [0, 1, 2, 3], 4900],
        ];

        foreach ($bundleSpecs as [$seller, $title, $indexes, $price]) {
            $own = $products->where('seller_id', $seller->id)->values();
            $bundle = Bundle::create([
                'seller_id' => $seller->id, 'license_id' => $this->licenses[1]->id, 'title' => $title, 'slug' => Str::slug($title),
                'description' => 'Everything at once with a Commercial license, at a big discount.', 'price_cents' => $price,
            ]);
            $bundle->products()->attach(collect($indexes)->map(fn ($i) => $own[$i]->id)->all());
        }

        $free = $products->firstWhere('is_free', true);
        Setting::put('free_of_the_week_product_id', $free->id);
    }

    /** Paid orders over the last 12 months, with the same fee maths as the real checkout. */
    private function orders(Collection $buyers, Collection $products, User $affiliate): void
    {
        $paid = $products->filter(fn ($p) => ! $p->is_free && $p->seller->sellerProfile->canReceivePayments())->values();
        $free = $products->where('is_free', true)->values();

        foreach ($buyers as $b => $buyer) {
            $orderCount = $b === 0 ? 3 : random_int(0, 3);

            for ($o = 0; $o < $orderCount; $o++) {
                $date = now()->subDays(random_int(1, 360))->setTime(random_int(8, 22), random_int(0, 59));
                $lines = $paid->random(random_int(1, 3))->map(fn ($p) => [$p, $this->licenses[$this->weightedLicense()]]);
                $ownTraffic = random_int(1, 100) <= 30;
                $viaAffiliate = ! $ownTraffic && random_int(1, 100) <= 20 ? $affiliate : null;

                $this->paidOrder($buyer, $lines, $date, $ownTraffic, $viaAffiliate);
            }

            if (random_int(1, 100) <= 40) {
                $this->freeClaim($buyer, $free->random(), now()->subDays(random_int(1, 300)));
            }
        }

        // One refunded purchase for the refund/verify screens.
        $item = Order::where('buyer_id', $buyers[0]->id)->with('items')->latest('id')->first()->items->first();
        $item->update(['refunded_at' => now()->subDays(3), 'refund_ref' => 'DEMO-REFUND-1']);
        LedgerEntry::create(['seller_id' => $item->seller_id, 'type' => LedgerType::Refund, 'amount_cents' => -$item->seller_net_cents, 'order_item_id' => $item->id, 'description' => 'Refund: '.$item->product_title]);
        Product::whereKey($item->product_id)->decrement('sales_count');
        if ($item->order->items()->whereNull('refunded_at')->doesntExist()) {
            $item->order->update(['status' => OrderStatus::Refunded]);
        }

        // An abandoned checkout, to show pending orders in admin.
        Order::forceCreate([
            'buyer_id' => $buyers[1]->id, 'subtotal_cents' => 1500, 'discount_cents' => 0, 'buyer_fee_cents' => 100,
            'total_cents' => 1600, 'status' => OrderStatus::Cancelled, 'gateway' => 'paypal', 'gateway_ref' => 'DEMO-CANCELLED-1',
        ]);
    }

    /** Personal licenses sell most, extended rarely. */
    private function weightedLicense(): int
    {
        $roll = random_int(1, 100);

        return $roll <= 60 ? 0 : ($roll <= 92 ? 1 : 2);
    }

    private function paidOrder(User $buyer, Collection $lines, Carbon $date, bool $ownTraffic, ?User $affiliate): void
    {
        $groups = $lines->groupBy(fn ($l) => $l[0]->seller_id);
        $order = Order::forceCreate([
            'buyer_id' => $buyer->id, 'subtotal_cents' => 0, 'discount_cents' => 0, 'buyer_fee_cents' => 0, 'total_cents' => 0,
            'status' => OrderStatus::Paid, 'gateway' => 'paypal', 'gateway_ref' => 'DEMO-'.strtoupper(Str::random(12)),
            'paid_at' => $date, 'created_at' => $date, 'updated_at' => $date,
        ]);

        $subtotal = $buyerFees = 0;
        // Own traffic applies to one seller only: the one whose link/shop brought the buyer.
        $referrer = $ownTraffic ? $groups->keys()->first() : null;

        foreach ($groups as $sellerId => $sellerLines) {
            $prices = $sellerLines->map(fn ($l) => $l[0]->priceFor($l[1]->id))->values()->all();
            $itemsTotal = array_sum($prices);
            $buyerFee = Fees::processingFee($itemsTotal);
            $buyerShares = Money::allocate($buyerFee, $prices);
            // What PayPal would have charged the seller on this unit (4.4% + 30¢).
            $paypalFee = Money::bps($itemsTotal + $buyerFee, 440) + 30;
            $paypalShares = Money::allocate($paypalFee, array_map(fn ($p, $f) => $p + $f, $prices, $buyerShares));

            foreach ($sellerLines->values() as $i => [$product, $license]) {
                $source = $referrer === $sellerId ? TrafficSource::Own : TrafficSource::Marketplace;
                $bps = $source->feeBps();
                $platformFee = Money::bps($prices[$i], $bps);
                $earns = $affiliate && $source === TrafficSource::Marketplace;
                $net = $prices[$i] + $buyerShares[$i] - $paypalShares[$i] - $platformFee;

                $item = $order->items()->forceCreate([
                    'product_id' => $product->id, 'license_id' => $license->id, 'seller_id' => $sellerId,
                    'product_title' => $product->title, 'license_name' => $license->name,
                    'list_price_cents' => $prices[$i], 'price_cents' => $prices[$i], 'buyer_fee_cents' => $buyerShares[$i],
                    'platform_fee_cents' => $platformFee, 'payment_fee_cents' => $paypalShares[$i], 'seller_net_cents' => $net,
                    'fee_bps' => $bps, 'attribution' => $source, 'purchase_code' => (string) Str::uuid(),
                    'gateway_capture_id' => 'DEMO-CAP-'.$sellerId.'-'.$order->id,
                    'affiliate_id' => $earns ? $affiliate->id : null,
                    'affiliate_commission_cents' => $earns ? min(Money::bps($prices[$i], Setting::int('affiliate_bps')), $platformFee) : 0,
                    // Older affiliate commissions have already been paid out.
                    'affiliate_paid_at' => $earns && $date->lt(now()->subMonths(2)) ? $date->copy()->addMonth() : null,
                    'created_at' => $date, 'updated_at' => $date,
                ]);

                LedgerEntry::forceCreate([
                    'seller_id' => $sellerId, 'type' => LedgerType::Sale, 'amount_cents' => $net, 'order_item_id' => $item->id,
                    'description' => "{$product->title} ({$license->name})", 'created_at' => $date, 'updated_at' => $date,
                ]);
                Product::whereKey($product->id)->increment('sales_count');
            }

            $subtotal += $itemsTotal;
            $buyerFees += $buyerFee;
        }

        $order->update(['subtotal_cents' => $subtotal, 'buyer_fee_cents' => $buyerFees, 'total_cents' => $subtotal + $buyerFees]);
    }

    private function freeClaim(User $buyer, Product $product, Carbon $date): void
    {
        $order = Order::forceCreate([
            'buyer_id' => $buyer->id, 'subtotal_cents' => 0, 'discount_cents' => 0, 'buyer_fee_cents' => 0, 'total_cents' => 0,
            'status' => OrderStatus::Paid, 'gateway' => 'free', 'paid_at' => $date, 'created_at' => $date, 'updated_at' => $date,
        ]);

        $order->items()->forceCreate([
            'product_id' => $product->id, 'license_id' => $this->licenses[0]->id, 'seller_id' => $product->seller_id,
            'product_title' => $product->title, 'license_name' => $this->licenses[0]->name, 'list_price_cents' => 0, 'price_cents' => 0,
            'attribution' => TrafficSource::Marketplace, 'purchase_code' => (string) Str::uuid(), 'created_at' => $date, 'updated_at' => $date,
        ]);
        Product::whereKey($product->id)->increment('sales_count');
    }

    /** Reviews, favorites, collections, follows, reports. */
    private function community(Collection $buyers, Collection $sellers, Collection $products): void
    {
        $comments = [
            5 => ['Exactly what I needed for a client project. Super clean files.', 'Beautiful work, well organised layers.', 'Saved me hours. Highly recommended!'],
            4 => ['Great quality, the help file could be a bit more detailed.', 'Very nice, would love more variations.'],
            3 => ['Good, but some elements needed cleanup.'],
            2 => ['Not quite what the previews suggested.'],
        ];

        $items = \App\Models\OrderItem::query()
            ->whereNull('refunded_at')
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid))
            ->with('order:id,buyer_id,paid_at')
            ->get();

        foreach ($items as $item) {
            if (random_int(1, 100) > 55) {
                continue;
            }

            $rating = [5, 5, 5, 4, 4, 3, 2][random_int(0, 6)];
            $review = Review::forceCreate([
                'order_item_id' => $item->id, 'product_id' => $item->product_id, 'user_id' => $item->order->buyer_id,
                'rating' => $rating, 'body' => random_int(1, 100) <= 80 ? fake()->randomElement($comments[$rating]) : null,
                'created_at' => $item->order->paid_at->copy()->addDays(random_int(1, 20)),
            ]);

            if (random_int(1, 100) <= 30) {
                $review->update(['seller_reply' => 'Thank you so much for the kind words! 🙏', 'replied_at' => $review->created_at->copy()->addDay()]);
            }
        }

        foreach ($products as $product) {
            \App\Actions\Community\SubmitReview::refreshRating($product);
        }

        foreach ($buyers as $i => $buyer) {
            $buyer->favorites()->syncWithoutDetaching($products->random(random_int(1, 6))->pluck('id')->all());
            $buyer->following()->syncWithoutDetaching($sellers->random(random_int(0, 3))->pluck('id')->all());

            if ($i % 4 === 0) {
                $collection = $buyer->collections()->create(['name' => fake()->randomElement(['Logo inspiration', 'Client: Café rebrand', 'Fonts to try', 'Mockups']), 'is_public' => $i % 8 === 0]);
                $collection->products()->attach($products->random(random_int(2, 5))->pluck('id')->all());
            }
        }

        Report::create([
            'product_id' => $products[10]->id, 'reporter_id' => $buyers[2]->id, 'reason' => 'copyright',
            'details' => 'This character set looks copied from my portfolio published in 2022. Same poses and colours.',
            'original_url' => 'https://www.behance.net/gallery/123456/wayang-characters',
        ]);
        Report::create([
            'product_id' => $products[17]->id, 'reporter_id' => $buyers[3]->id, 'reason' => 'other',
            'details' => 'The download contains only a README file, the PSD is missing.',
        ]);
    }
}
