<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\License;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $licenses = [
            ['Personal', 'Use in personal, non-commercial projects. Not for resale or client work.'],
            ['Commercial', 'Use in unlimited commercial projects for yourself or one client. End products may be sold, but not the item itself.'],
            ['Extended', 'Commercial use including products for sale where the item is a core part of the value (e.g. merchandise, templates, apps).'],
        ];

        foreach ($licenses as $sort => [$name, $description]) {
            License::updateOrCreate(['slug' => Str::slug($name)], compact('name', 'description', 'sort'));
        }

        $categories = [
            'Fonts' => ['Sans Serif', 'Serif', 'Script', 'Display'],
            'Graphics' => ['Icons', 'Illustrations', 'Patterns', 'Textures'],
            'Templates' => ['Presentations', 'Social Media', 'Print', 'Logos', 'Resumes'],
            'Mockups' => ['Branding', 'Apparel', 'Devices', 'Packaging'],
            'UI Kits' => ['Web', 'Mobile', 'Dashboards'],
            'Add-ons' => ['Brushes', 'Actions & Presets', 'Layer Styles'],
        ];

        $sort = 0;
        foreach ($categories as $parentName => $children) {
            $parent = Category::updateOrCreate(['slug' => Str::slug($parentName)], ['name' => $parentName, 'sort' => $sort++]);

            foreach ($children as $i => $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($parentName.' '.$childName)],
                    ['name' => $childName, 'parent_id' => $parent->id, 'sort' => $i],
                );
            }
        }
    }
}
