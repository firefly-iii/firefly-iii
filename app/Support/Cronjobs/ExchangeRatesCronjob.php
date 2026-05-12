<?php

/**
 * ExchangeRatesCronjob.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Cronjobs;

use Carbon\Carbon;
use FireflyIII\Jobs\DownloadExchangeRates;
use FireflyIII\Jobs\DownloadNationalExchangeRates;
use FireflyIII\Models\Configuration;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Support\Facades\Log;

/**
 * Class ExchangeRatesCronjob
 */
class ExchangeRatesCronjob extends AbstractCronjob
{
    public function fire(): void
    {
        /** @var Configuration $config */
        $config        = FireflyConfig::get('last_cer_job', 0);
        $lastTime      = (int) $config->data;
        $diff          = now(config('app.timezone'))->getTimestamp() - $lastTime;
        $diffForHumans = now(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);
        if (0 === $lastTime) {
            Log::info('Exchange rates cron-job has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43_200) {
            Log::info(sprintf('It has been %s since the exchange rates cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                Log::info('The exchange rates cron-job will not fire now.');
                $this->message = sprintf('It has been %s since the exchange rates cron-job has fired. It will not fire now.', $diffForHumans);

                return;
            }

            Log::info('Execution of the exchange rates cron-job has been FORCED.');
        }

        if ($lastTime > 0 && $diff > 43_200) {
            Log::info(sprintf('It has been %s since the exchange rates cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireExchangeRateJob();
        Preferences::mark();
    }

    private function fireExchangeRateJob(): void
    {
        Log::info(sprintf('Will now fire exchange rates cron job task for date "%s".', $this->date->format('Y-m-d')));

        $source = (string) FireflyConfig::get('exchange_rate_source', config('cer.source'))->data;
        Log::info(sprintf('Exchange rate source is "%s".', $source));

        switch ($source) {
            case 'country_national':
                $this->runNationalProviders();
                break;
            case 'internal':
                // "Internal" mode relies on the static fallback rates already
                // shipped in config/cer.php — nothing to download. We still
                // mark the cron as fired so the UI shows it as alive.
                Log::info('Internal exchange-rate source selected — skipping download.');
                break;
            case 'external':
            default:
                /** @var DownloadExchangeRates $job */
                $job = app(DownloadExchangeRates::class);
                $job->setDate($this->date);
                $job->handle();
                break;
        }

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = sprintf('Exchange rates cron job fired successfully (source: %s).', $source);

        FireflyConfig::set('last_cer_job', (int) $this->date->format('U'));
        Log::info('Done with exchange rates job task.');
    }

    private function runNationalProviders(): void
    {
        /** @var DownloadNationalExchangeRates $job */
        $job = app(DownloadNationalExchangeRates::class);
        $job->setDate($this->date);
        $written = $job->handle(
            app(\FireflyIII\Services\ExchangeRate\NationalRateProviderRegistry::class),
            app(\FireflyIII\Services\ExchangeRate\NationalRatesAdapter::class),
            app(\FireflyIII\Services\ExchangeRate\UserCountryResolver::class),
        );
        Log::info(sprintf('National exchange-rate adapter wrote %d rows.', $written));
    }
}
