<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'EU', 'name' => 'European Union', 'flag_src' => 'eu_EU.png'],
            ['code' => 'BY', 'name' => 'Belarus', 'flag_src' => 'by_BY.png'],
            ['code' => 'PL', 'name' => 'Poland', 'flag_src' => 'pl_PL.png'],
            ['code' => 'UA', 'name' => 'Ukraine', 'flag_src' => 'uk_UA.png'],
            ['code' => 'RU', 'name' => 'Russia', 'flag_src' => 'ru_RU.png'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'flag_src' => 'default.png'],
            ['code' => 'US', 'name' => 'United States', 'flag_src' => 'en_US.png'],
            ['code' => 'CA', 'name' => 'Canada', 'flag_src' => 'default.png'],
            ['code' => 'MX', 'name' => 'Mexico', 'flag_src' => 'default.png'],
            ['code' => 'BR', 'name' => 'Brazil', 'flag_src' => 'pt_BR.png'],
            ['code' => 'AR', 'name' => 'Argentina', 'flag_src' => 'default.png'],
            ['code' => 'CO', 'name' => 'Colombia', 'flag_src' => 'default.png'],
            ['code' => 'CL', 'name' => 'Chile', 'flag_src' => 'default.png'],
            ['code' => 'UY', 'name' => 'Uruguay', 'flag_src' => 'default.png'],
            ['code' => 'PE', 'name' => 'Peru', 'flag_src' => 'default.png'],
            ['code' => 'AU', 'name' => 'Australia', 'flag_src' => 'default.png'],
            ['code' => 'NZ', 'name' => 'New Zealand', 'flag_src' => 'default.png'],
            ['code' => 'EG', 'name' => 'Egypt', 'flag_src' => 'default.png'],
            ['code' => 'MA', 'name' => 'Morocco', 'flag_src' => 'default.png'],
            ['code' => 'ZA', 'name' => 'South Africa', 'flag_src' => 'default.png'],
            ['code' => 'JP', 'name' => 'Japan', 'flag_src' => 'ja_JP.png'],
            ['code' => 'CN', 'name' => 'China', 'flag_src' => 'zh_CN.png'],
            ['code' => 'KR', 'name' => 'South Korea', 'flag_src' => 'default.png'],
            ['code' => 'IN', 'name' => 'India', 'flag_src' => 'default.png'],
            ['code' => 'IL', 'name' => 'Israel', 'flag_src' => 'he_IL.png'],
            ['code' => 'CH', 'name' => 'Switzerland', 'flag_src' => 'default.png'],
            ['code' => 'HR', 'name' => 'Croatia', 'flag_src' => 'default.png'],
            ['code' => 'HK', 'name' => 'Hong Kong', 'flag_src' => 'default.png'],
            ['code' => 'CZ', 'name' => 'Czech Republic', 'flag_src' => 'cs_CZ.png'],
            ['code' => 'KZ', 'name' => 'Kazakhstan', 'flag_src' => 'default.png'],
            ['code' => 'SA', 'name' => 'Saudi Arabia', 'flag_src' => 'default.png'],
            ['code' => 'RS', 'name' => 'Serbia', 'flag_src' => 'sr_CS.png'],
            ['code' => 'TW', 'name' => 'Taiwan', 'flag_src' => 'zh_TW.png'],
            ['code' => 'TH', 'name' => 'Thailand', 'flag_src' => 'default.png'],
            ['code' => 'DK', 'name' => 'Denmark', 'flag_src' => 'da_DK.png'],
            ['code' => 'IS', 'name' => 'Iceland', 'flag_src' => 'default.png'],
            ['code' => 'NO', 'name' => 'Norway', 'flag_src' => 'nb_NO.png'],
            ['code' => 'SE', 'name' => 'Sweden', 'flag_src' => 'sv_SE.png'],
            ['code' => 'RO', 'name' => 'Romania', 'flag_src' => 'ro_RO.png'],
            ['code' => 'TR', 'name' => 'Turkey', 'flag_src' => 'tr_TR.png'],
        ];

        foreach ($countries as $country) {
            try {
                DB::table('countries')
                    ->where('code', $country['code'])
                    ->update(['flag_src' => $country['flag_src']]);
            } catch (PDOException $e) {
                Log::debug(sprintf(
                    'Failed updating flag for country "%s": %s',
                    $country['code'],
                    $e->getMessage()
                ));
            }
        }
    }
}
