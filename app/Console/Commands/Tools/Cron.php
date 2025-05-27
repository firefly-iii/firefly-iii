<?php

/*
 * Cron.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Tools;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Cronjobs\AutoBudgetCronjob;
use FireflyIII\Support\Cronjobs\BillWarningCronjob;
use FireflyIII\Support\Cronjobs\ExchangeRatesCronjob;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use FireflyIII\Support\Cronjobs\UpdateCheckCronjob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class Cron extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Runs all Firefly III cron-job related commands. Configure a cron job according to the official Firefly III documentation.';

    protected $signature   = 'firefly-iii:cron
        {--F|force : Force the cron job(s) to execute.}
        {--date= : Set the date in YYYY-MM-DD to make Firefly III think that\'s the current date.}
        {--check-version : Check if there is a new Firefly III version. Other tasks will be skipped unless also requested.}
        {--download-cer : Download exchange rates. Other tasks will be skipped unless also requested.}
        {--create-recurring : Create recurring transactions. Other tasks will be skipped unless also requested.}
        {--create-auto-budgets : Create auto budgets. Other tasks will be skipped unless also requested.}
        {--send-bill-warnings : Send bill warnings. Other tasks will be skipped unless also requested.}
        ';

    public function handle(): int
    {
        $doAll = !$this->option('download-cer')
                 && !$this->option('create-recurring')
                 && !$this->option('create-auto-budgets')
                 && !$this->option('send-bill-warnings')
                 && !$this->option('check-version');
        $date  = null;

        try {
            $date = new Carbon($this->option('date'));
        } catch (InvalidArgumentException $e) {
            $this->friendlyError(sprintf('"%s" is not a valid date', $this->option('date')));
        }
        $force = (bool) $this->option('force'); // @phpstan-ignore-line

        // Fire exchange rates cron job.
        if (true === config('cer.download_enabled') && ($doAll || $this->option('download-cer'))) {
            try {
                $this->exchangeRatesCronJob($force, $date);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $this->friendlyError($e->getMessage());
            }
        }

        // check for new version
        if ($doAll || $this->option('check-version')) {
            try {
                $this->checkForUpdates($force);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $this->friendlyError($e->getMessage());
            }
        }

        // Fire recurring transaction cron job.
        if ($doAll || $this->option('create-recurring')) {
            try {
                $this->recurringCronJob($force, $date);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $this->friendlyError($e->getMessage());
            }
        }

        // Fire auto-budget cron job:
        if ($doAll || $this->option('create-auto-budgets')) {
            try {
                $this->autoBudgetCronJob($force, $date);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $this->friendlyError($e->getMessage());
            }
        }

        // Fire bill warning cron job
        if ($doAll || $this->option('send-bill-warnings')) {
            try {
                $this->billWarningCronJob($force, $date);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $this->friendlyError($e->getMessage());
            }
        }

        $this->friendlyInfo('More feedback on the cron jobs can be found in the log files.');

        return 0;
    }

    private function exchangeRatesCronJob(bool $force, ?Carbon $date): void
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $exchangeRates = new ExchangeRatesCronjob();
        $exchangeRates->setForce($force);
        // set date in cron job:
        if (null !== $date) {
            $exchangeRates->setDate($date);
        }

        $exchangeRates->fire();

        if ($exchangeRates->jobErrored) {
            $this->friendlyError(sprintf('Error in "exchange rates" cron: %s', $exchangeRates->message));
        }
        if ($exchangeRates->jobFired) {
            $this->friendlyInfo(sprintf('"Exchange rates" cron fired: %s', $exchangeRates->message));
        }
        if ($exchangeRates->jobSucceeded) {
            $this->friendlyPositive(sprintf('"Exchange rates" cron ran with success: %s', $exchangeRates->message));
        }
    }

    private function checkForUpdates(bool $force): void
    {
        $updateCheck = new UpdateCheckCronjob();
        $updateCheck->setForce($force);
        $updateCheck->fire();

        if ($updateCheck->jobErrored) {
            $this->friendlyError(sprintf('Error in "update check" cron: %s', $updateCheck->message));
        }
        if ($updateCheck->jobFired) {
            $this->friendlyInfo(sprintf('"Update check" cron fired: %s', $updateCheck->message));
        }
        if ($updateCheck->jobSucceeded) {
            $this->friendlyPositive(sprintf('"Update check" cron ran with success: %s', $updateCheck->message));
        }
    }

    /**
     * @throws FireflyException
     */
    private function recurringCronJob(bool $force, ?Carbon $date): void
    {
        $recurring = new RecurringCronjob();
        $recurring->setForce($force);

        // set date in cron job:
        if (null !== $date) {
            $recurring->setDate($date);
        }

        $recurring->fire();
        if ($recurring->jobErrored) {
            $this->friendlyError(sprintf('Error in "create recurring transactions" cron: %s', $recurring->message));
        }
        if ($recurring->jobFired) {
            $this->friendlyInfo(sprintf('"Create recurring transactions" cron fired: %s', $recurring->message));
        }
        if ($recurring->jobSucceeded) {
            $this->friendlyPositive(sprintf('"Create recurring transactions" cron ran with success: %s', $recurring->message));
        }
    }

    private function autoBudgetCronJob(bool $force, ?Carbon $date): void
    {
        $autoBudget = new AutoBudgetCronjob();
        $autoBudget->setForce($force);
        // set date in cron job:
        if (null !== $date) {
            $autoBudget->setDate($date);
        }

        $autoBudget->fire();

        if ($autoBudget->jobErrored) {
            $this->friendlyError(sprintf('Error in "create auto budgets" cron: %s', $autoBudget->message));
        }
        if ($autoBudget->jobFired) {
            $this->friendlyInfo(sprintf('"Create auto budgets" cron fired: %s', $autoBudget->message));
        }
        if ($autoBudget->jobSucceeded) {
            $this->friendlyPositive(sprintf('"Create auto budgets" cron ran with success: %s', $autoBudget->message));
        }
    }

    /**
     * @throws FireflyException
     */
    private function billWarningCronJob(bool $force, ?Carbon $date): void
    {
        $autoBudget = new BillWarningCronjob();
        $autoBudget->setForce($force);
        // set date in cron job:
        if (null !== $date) {
            $autoBudget->setDate($date);
        }

        $autoBudget->fire();

        if ($autoBudget->jobErrored) {
            $this->friendlyError(sprintf('Error in "bill warnings" cron: %s', $autoBudget->message));
        }
        if ($autoBudget->jobFired) {
            $this->friendlyInfo(sprintf('"Send bill warnings" cron fired: %s', $autoBudget->message));
        }
        if ($autoBudget->jobSucceeded) {
            $this->friendlyPositive(sprintf('"Send bill warnings" cron ran with success: %s', $autoBudget->message));
        }
    }
}
