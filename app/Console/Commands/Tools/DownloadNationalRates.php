<?php

/*
 * DownloadNationalRates.php
 *
 * Artisan command for manually triggering the national-bank exchange-rate
 * pipeline. Useful for first-run population and for debugging providers
 * without waiting for the cron job.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Tools;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Jobs\DownloadNationalExchangeRates;
use FireflyIII\Services\ExchangeRate\NationalRateProviderRegistry;
use FireflyIII\Services\ExchangeRate\NationalRatesAdapter;
use FireflyIII\Services\ExchangeRate\UserCountryResolver;
use Illuminate\Console\Command;

class DownloadNationalRates extends Command
{
    use ShowsFriendlyMessages;

    protected $signature   = 'firefly-iii:download-national-rates
        {--date= : Date in YYYY-MM-DD format (defaults to today)}
        {--country= : Force a single country code instead of using user preferences}';

    protected $description = 'Pull exchange rates from national banks selected by users (or by --country).';

    public function handle(
        NationalRateProviderRegistry $registry,
        NationalRatesAdapter $adapter,
        UserCountryResolver $resolver,
    ): int {
        $dateOpt = $this->option('date');
        $date    = is_string($dateOpt) && '' !== $dateOpt
            ? Carbon::createFromFormat('Y-m-d', $dateOpt)
            : Carbon::now(config('app.timezone'));
        if (!$date instanceof Carbon) {
            $this->friendlyError('Invalid --date value, expected YYYY-MM-DD.');

            return self::FAILURE;
        }
        $date->startOfDay();

        $country = (string) $this->option('country');
        if ('' !== $country) {
            return $this->runSingle($registry, $adapter, $date, strtoupper($country));
        }

        $this->friendlyLine(sprintf('Running national-rate providers for %s...', $date->format('Y-m-d')));

        /** @var DownloadNationalExchangeRates $job */
        $job     = app(DownloadNationalExchangeRates::class);
        $job->setDate($date);
        $written = $job->handle($registry, $adapter, $resolver);

        $this->friendlyPositive(sprintf('Done. %d exchange-rate rows written.', $written));

        return self::SUCCESS;
    }

    private function runSingle(
        NationalRateProviderRegistry $registry,
        NationalRatesAdapter $adapter,
        Carbon $date,
        string $country,
    ): int {
        if (!$registry->hasProviderFor($country)) {
            $this->friendlyError(sprintf(
                'No provider registered for country "%s". Available: %s',
                $country,
                implode(', ', $registry->supportedCountries()),
            ));

            return self::FAILURE;
        }
        $provider = $registry->get($country);
        $this->friendlyLine(sprintf(
            'Running %s for %s (forced country %s)...',
            $provider::name(),
            $date->format('Y-m-d'),
            $country,
        ));
        $written = $adapter->pullAndStore($provider, $date);
        $this->friendlyPositive(sprintf('Done. %d exchange-rate rows written.', $written));

        return self::SUCCESS;
    }
}
