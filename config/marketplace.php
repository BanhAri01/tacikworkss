<?php

return [

    // Initial admin account created by AdminUserSeeder.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@tacikworks.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    // Disk for private product zips and preview sources ("products" local, "products_r2" in prod).
    'products_disk' => env('PRODUCTS_DISK', 'products'),

    'previews' => [
        'thumb_width' => 600,
        'large_width' => 1600,
        'quality' => 82,
        'watermark_font' => resource_path('fonts/Poppins-SemiBold.ttf'),
    ],

    // Defaults for keys in the settings table (admin can override). Basis points: 1000 = 10%.
    'defaults' => [
        'fee_own_bps' => 1000,
        'fee_marketplace_bps' => 3000,
        'processing_fee_bps' => 440,
        'processing_fee_fixed_cents' => 30,
        'free_of_the_week_product_id' => null,
        // Affiliates earn this share of the item price on marketplace sales, paid from the platform fee.
        'affiliate_bps' => 1000,
        'affiliate_payout_min_cents' => 2000,
    ],

    'attribution' => [
        'cookie' => 'dm_ref',
        'affiliate_cookie' => 'dm_aff',
        'days' => 30,
    ],

    'download_link_minutes' => 15,

    // Choices offered on the product form and search filters.
    'file_formats' => [
        'PSD', 'AI', 'EPS', 'SVG', 'PNG', 'JPG', 'PDF', 'FIG', 'SKETCH', 'XD', 'INDD', 'OTF', 'TTF',
        'WOFF', 'ABR', 'ATN', 'PPTX', 'KEY', 'AEP', 'MOGRT', 'PROCREATE', 'BLEND', 'OBJ', 'DOCX',
    ],
    'compatible_software' => [
        'Photoshop', 'Illustrator', 'InDesign', 'Figma', 'Sketch', 'Adobe XD', 'Affinity Designer',
        'Affinity Photo', 'Canva', 'PowerPoint', 'Keynote', 'Google Slides', 'After Effects',
        'Premiere Pro', 'Procreate', 'Blender', 'CorelDRAW', 'Microsoft Word',
    ],

    // PayPal limit on purchase units (one per seller) in one order.
    'max_sellers_per_checkout' => 10,

];
