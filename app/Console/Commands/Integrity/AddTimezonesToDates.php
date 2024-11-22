<?php

declare(strict_types=1);
/*
 * AddTimezonesToDates.php
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AddTimezonesToDates extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature        = 'firefly-iii:add-timezones-to-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description      = 'Make sure all dates have a timezone.';

    public static array $models = [
        AccountBalance::class       => ['date'], // done
        AvailableBudget::class      => ['start_date', 'end_date'], // done
        Bill::class                 => ['date', 'end_date', 'extension_date'], // done
        BudgetLimit::class          => ['start_date', 'end_date'], // done
        CurrencyExchangeRate::class => ['date'], // done
        InvitedUser::class          => ['expires'],
        PiggyBankEvent::class       => ['date'],
        PiggyBankRepetition::class  => ['startdate', 'targetdate'],
        PiggyBank::class            => ['startdate', 'targetdate'], // done
        Recurrence::class           => ['first_date', 'repeat_until', 'latest_date'],
        Tag::class                  => ['date'],
        TransactionJournal::class   => ['date'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach (self::$models as $model => $fields) {
            $this->addTimezoneToModel($model, $fields);
        }
        // not yet in UTC mode
        FireflyConfig::set('utc', false);
    }

    private function addTimezoneToModel(string $model, array $fields): void
    {
        foreach ($fields as $field) {
            $this->addTimezoneToModelField($model, $field);
        }
    }

    private function addTimezoneToModelField(string $model, string $field): void
    {
        $shortModel    = str_replace('FireflyIII\Models\\', '', $model);
        $timezoneField = sprintf('%s_tz', $field);
        $count         = 0;

        try {
            $count = $model::whereNull($timezoneField)->count();
        } catch (QueryException $e) {
            $this->friendlyError(sprintf('Cannot add timezone information to field "%s" of model "%s". Field does not exist.', $field, $shortModel));
            Log::error($e->getMessage());
        }
        if (0 === $count) {
            $this->friendlyPositive(sprintf('Timezone information is present in field "%s" of model "%s".', $field, $shortModel));

            return;
        }
        $this->friendlyInfo(sprintf('Adding timezone information to field "%s" of model "%s".', $field, $shortModel));

        $model::whereNull($timezoneField)->update([$timezoneField => config('app.timezone')]);
    }
}
