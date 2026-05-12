<?php

/*
 * DownloadNationalExchangeRates.php
 *
 * Fires every registered national-bank provider that at least one user
 * is currently subscribed to via their `national_rates_country`
 * preference, and stores the produced rates through NationalRatesAdapter.
 */

declare(strict_types=1);

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\NationalRateProviderRegistry;
use FireflyIII\Services\ExchangeRate\NationalRatesAdapter;
use FireflyIII\Services\ExchangeRate\UserCountryResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DownloadNationalExchangeRates implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Carbon $date;

    public function __construct(?Carbon $date = null)
    {
        $this->date = ($date instanceof Carbon ? clone $date : Carbon::now(config('app.timezone')))->startOfDay();
    }

    public function setDate(Carbon $date): void
    {
        $this->date = (clone $date)->startOfDay();
    }

    public function handle(
        NationalRateProviderRegistry $registry,
        NationalRatesAdapter $adapter,
        UserCountryResolver $resolver,
    ): int {
        Log::debug(sprintf('DownloadNationalExchangeRates::handle for %s', $this->date->format('Y-m-d')));

        $countries = $resolver->activeCountryCodes();
        if ([] === $countries) {
            Log::info('[DownloadNationalExchangeRates] No users have selected a national-rates country; nothing to do.');

            return 0;
        }

        $totalWritten = 0;
        foreach ($countries as $countryCode) {
            try {
                $provider     = $registry->get($countryCode);
                $totalWritten += $adapter->pullAndStore($provider, $this->date);
            } catch (Throwable $e) {
                Log::error(sprintf(
                    '[DownloadNationalExchangeRates] Provider for %s failed: %s',
                    $countryCode,
                    $e->getMessage(),
                ));
            }
        }

        return $totalWritten;
    }
}
