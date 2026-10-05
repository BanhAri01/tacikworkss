<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Locale;

class Countries
{
    /** Region codes that intl names but which are not countries. */
    private const EXCLUDED = ['EU', 'EZ', 'QO', 'UN', 'ZZ', 'XA', 'XB'];

    /**
     * ISO 3166-1 alpha-2 code => English name, sorted by name (built from the intl extension).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return Cache::rememberForever('countries.en', function () {
            $countries = [];

            foreach (range('A', 'Z') as $a) {
                foreach (range('A', 'Z') as $b) {
                    $code = $a.$b;
                    $name = Locale::getDisplayRegion('-'.$code, 'en');

                    if ($name !== $code && ! in_array($code, self::EXCLUDED, true)) {
                        $countries[$code] = $name;
                    }
                }
            }

            asort($countries);

            return $countries;
        });
    }
}
