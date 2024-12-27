<?php

/*
 * TriggerCreditCalculation.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use Illuminate\Console\Command;

class TriggersCreditCalculation extends Command
{
    protected $description = 'Triggers the credit recalculation service for liabilities.';
    protected $signature   = 'correction:recalculates-liabilities';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->processAccounts();

        return 0;
    }

    private function processAccounts(): void
    {
        $accounts = Account::leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->whereIn('account_types.type', config('firefly.valid_liabilities'))
            ->get(['accounts.*'])
        ;
        foreach ($accounts as $account) {
            $this->processAccount($account);
        }
    }

    private function processAccount(Account $account): void
    {
        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccount($account);
        $object->recalculate();
    }
}
