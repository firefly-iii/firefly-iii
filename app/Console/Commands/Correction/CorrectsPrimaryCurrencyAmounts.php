<?php

/*
 * CorrectsPrimaryCurrencyAmounts.php
 * Copyright (c) 2025 james@firefly-iii.org.
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Handlers\Observer\TransactionObserver;
use FireflyIII\Models\Account;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Services\Internal\Recalculate\PrimaryAmountRecalculationService;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrectsPrimaryCurrencyAmounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Recalculate primary currency amounts for all objects.';

    protected $signature   = 'correction:recalculate-pc-amounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (false === FireflyConfig::get('enable_exchange_rates', config('cer.enabled'))->data) {
            $this->friendlyInfo('This command will not run because currency exchange rates are disabled.');

            return 0;
        }
        Log::debug('Will update all primary currency amounts. This may take some time.');
        $this->friendlyWarning('Recalculating primary currency amounts for all objects. This may take some time!');

        $calculator = new PrimaryAmountRecalculationService();
        $calculator->recalculate();

        $this->friendlyInfo('Recalculated all primary currency amounts.');

        return 0;
    }


}
