<?php

/**
 * CCLiabilities.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class UpgradesCreditCardLiabilities extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_cc_liabilities';
    protected $description          = 'Convert old credit card liabilities.';
    protected $signature            = 'upgrade:480-cc-liabilities {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        $ccType   = AccountType::where('type', AccountType::CREDITCARD)->first();
        $debtType = AccountType::where('type', AccountType::DEBT)->first();
        if (null === $ccType || null === $debtType) {
            $this->markAsExecuted();

            return 0;
        }

        /** @var Collection $accounts */
        $accounts = Account::where('account_type_id', $ccType->id)->get();
        foreach ($accounts as $account) {
            $account->account_type_id = $debtType->id;
            $account->save();
            $this->friendlyInfo(sprintf('Converted credit card liability account "%s" (#%d) to generic debt liability.', $account->name, $account->id));
        }
        if ($accounts->count() > 0) {
            $this->friendlyWarning(
                'Credit card liability types are no longer supported and have been converted to generic debts. See: https://bit.ly/FF3-credit-cards'
            );
        }
        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);

        return (bool) $configVar?->data;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
