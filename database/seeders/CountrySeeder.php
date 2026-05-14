<?php

declare(strict_types=1);

namespace Database\Seeders;

use FireflyIII\Models\Country;
use FireflyIII\Services\ExchangeRate\Providers\CbrProvider;
use FireflyIII\Services\ExchangeRate\Providers\EcbProvider;
use FireflyIII\Services\ExchangeRate\Providers\NbrbProvider;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Provider mapping: ISO-3166 alpha-2 => FQCN implementing
        // NationalRateProviderInterface. Countries omitted from this map
        // (or with a null entry) are seeded without a provider and will
        // be hidden from the administration country selector.
        $providers = [
            'BY' => NbrbProvider::class,
            'RU' => CbrProvider::class,
            'EU' => EcbProvider::class,
        ];

        $countries = [
            ['code' => 'EU', 'name' => 'European Union'],
            ['code' => 'BY', 'name' => 'Belarus'],
            ['code' => 'PL', 'name' => 'Poland'],
            ['code' => 'UA', 'name' => 'Ukraine'],
            ['code' => 'RU', 'name' => 'Russia'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'AR', 'name' => 'Argentina'],
            ['code' => 'CO', 'name' => 'Colombia'],
            ['code' => 'CL', 'name' => 'Chile'],
            ['code' => 'UY', 'name' => 'Uruguay'],
            ['code' => 'PE', 'name' => 'Peru'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'NZ', 'name' => 'New Zealand'],
            ['code' => 'EG', 'name' => 'Egypt'],
            ['code' => 'MA', 'name' => 'Morocco'],
            ['code' => 'ZA', 'name' => 'South Africa'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'CN', 'name' => 'China'],
            ['code' => 'KR', 'name' => 'South Korea'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'IL', 'name' => 'Israel'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'HR', 'name' => 'Croatia'],
            ['code' => 'HK', 'name' => 'Hong Kong'],
            ['code' => 'CZ', 'name' => 'Czech Republic'],
            ['code' => 'KZ', 'name' => 'Kazakhstan'],
            ['code' => 'SA', 'name' => 'Saudi Arabia'],
            ['code' => 'RS', 'name' => 'Serbia'],
            ['code' => 'TW', 'name' => 'Taiwan'],
            ['code' => 'TH', 'name' => 'Thailand'],
            ['code' => 'DK', 'name' => 'Denmark'],
            ['code' => 'IS', 'name' => 'Iceland'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'RO', 'name' => 'Romania'],
            ['code' => 'TR', 'name' => 'Turkey'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                [
                    'name'           => $country['name'],
                    'provider_class' => $providers[$country['code']] ?? null,
                ]
            );
        }
    }
}
