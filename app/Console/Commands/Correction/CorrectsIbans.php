<?php

/*
 * FixIbans.php
 * Copyright (c) 2022 james@firefly-iii.org
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

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CorrectsIbans extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Removes spaces from IBANs';
    protected $signature   = 'correction:ibans';
    private int $count     = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $accounts = Account::whereNotNull('iban')->get();
        $this->filterIbans($accounts);
        $this->countAndCorrectIbans($accounts);

        return 0;
    }

    private function filterIbans(Collection $accounts): void
    {
        /** @var Account $account */
        foreach ($accounts as $account) {
            $iban    = (string) $account->iban;
            $newIban = app('steam')->filterSpaces($iban);
            if ('' !== $iban && $iban !== $newIban) {
                $account->iban = $newIban;
                $account->save();
                $this->friendlyInfo(sprintf('Removed spaces from IBAN of account #%d', $account->id));
                ++$this->count;
            }
        }
    }

    private function countAndCorrectIbans(Collection $accounts): void
    {
        $set = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $userId = $account->user_id;
            $set[$userId] ??= [];
            $iban   = (string) $account->iban;
            if ('' === $iban) {
                continue;
            }
            $type   = $account->accountType->type;
            if (in_array($type, [AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE], true)) {
                $type = 'liabilities';
            }
            if (array_key_exists($iban, $set[$userId])) {
                // iban already in use! two exceptions exist:
                if (
                    !(AccountType::EXPENSE === $set[$userId][$iban] && AccountType::REVENUE === $type) // allowed combination
                    && !(AccountType::REVENUE === $set[$userId][$iban] && AccountType::EXPENSE === $type) // also allowed combination.
                ) {
                    $this->friendlyWarning(
                        sprintf(
                            'IBAN "%s" is used more than once and will be removed from %s #%d ("%s")',
                            $iban,
                            $account->accountType->type,
                            $account->id,
                            $account->name
                        )
                    );
                    $account->iban = null;
                    $account->save();
                    ++$this->count;
                }
            }

            if (!array_key_exists($iban, $set[$userId])) {
                $set[$userId][$iban] = $type;
            }
        }
    }
}
