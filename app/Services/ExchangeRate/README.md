# National Bank Exchange Rate Providers

Pluggable layer that pulls daily FX rates from country-level central
banks and stores them in the existing `currency_exchange_rates` table,
side-by-side with rates produced by `DownloadExchangeRates`.

## Architecture

```
ExchangeRatesCronjob
       │   (reads FireflyConfig['exchange_rate_source'])
       ▼
 ┌─────────────────────────────┐
 │ DownloadExchangeRates       │  ← source = "external" (Azure)
 │ DownloadNationalExchangeRates│  ← source = "country_national"
 └─────────────────────────────┘
                │
                ▼
       NationalRatesAdapter
                │
                ▼
       currency_exchange_rates (per user, both directions)
```

### Components

- `Providers\NationalRateProviderInterface` — contract.
- `Providers\AbstractNationalRateProvider` — Guzzle scaffolding + logging.
- `Providers\NbrbProvider` — Belarus, JSON, BYN-based.
- `Providers\CbrProvider` — Russia, XML (windows-1251), RUB-based.
- `Providers\EcbProvider` — EU, XML, EUR-based.
- `RateQuote` — immutable DTO returned by providers.
- `NationalRateProviderRegistry` — config-driven country → provider map.
- `UserCountryResolver` — reads the per-user `national_rates_country` preference.
- `NationalRatesAdapter` — persists `RateQuote[]` for every user, in both
  directions, idempotently.

### Config

Register or unregister providers in `config/cer.php`:

```php
'national_providers' => [
    'BY' => NbrbProvider::class,
    'RU' => CbrProvider::class,
    'EU' => EcbProvider::class,
],
```

The admin selects the source globally in `/admin/configuration`
(`exchange_rate_source` = `external|internal|country_national`).
Each user then picks their country in `Profile → Preferences`.

## Adding a new provider

1. Create `app/Services/ExchangeRate/Providers/MyBankProvider.php`
   extending `AbstractNationalRateProvider`. Implement:
   - `static country(): string` — ISO-3166 alpha-2.
   - `static base(): string` — ISO-4217 base currency.
   - `static name(): string` — short label for logs.
   - `fetchRates(Carbon $date): array<RateQuote>` — must normalise to
     "1 unit of base = X foreign".
2. Register the class in `config/cer.php` under its country code.
3. Make sure the country is seeded in `database/seeders/CountrySeeder.php`.
4. Test:
   ```
   php artisan firefly-iii:download-national-rates --country=XX
   ```

## Manual run

```
# uses every country at least one user has selected
php artisan firefly-iii:download-national-rates

# force a single country (admin / debug)
php artisan firefly-iii:download-national-rates --country=BY --date=2026-05-12
```
