<?php
/**
 * EnableCurrencies.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Class EnableCurrencies
 */
class EnableCurrencies extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Enables all currencies in use.';
    protected $signature   = 'firefly-iii:enable-currencies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $found = [];
        // get all meta entries
        /** @var Collection $meta */
        $meta = AccountMeta::where('name', 'currency_id')->groupBy('data')->get(['data']);
        foreach ($meta as $entry) {
            $found[] = (int)$entry->data;
        }

        // get all from journals:
        $journals = TransactionJournal::groupBy('transaction_currency_id')->get(['transaction_currency_id']);
        foreach ($journals as $entry) {
            $found[] = (int)$entry->transaction_currency_id;
        }

        // get all from transactions
        $transactions = Transaction::groupBy('transaction_currency_id', 'foreign_currency_id')->get(['transaction_currency_id', 'foreign_currency_id']);
        foreach ($transactions as $entry) {
            $found[] = (int)$entry->transaction_currency_id;
            $found[] = (int)$entry->foreign_currency_id;
        }

        // get all from budget limits
        $limits = BudgetLimit::groupBy('transaction_currency_id')->get(['transaction_currency_id']);
        foreach ($limits as $entry) {
            $found[] = (int)$entry->transaction_currency_id;
        }

        $found    = array_values(array_unique($found));
        $found    = array_values(
            array_filter(
                $found,
                function (int $currencyId) {
                    return $currencyId !== 0;
                }
            )
        );
        $disabled = TransactionCurrency::whereIn('id', $found)->where('enabled', false)->count();
        if ($disabled > 0) {
            $this->friendlyInfo(sprintf('%d currencies were (was) disabled while in use by transactions. This has been corrected.', $disabled));
        }
        if (0 === $disabled) {
            $this->friendlyPositive('All currencies are correctly enabled or disabled.');
        }
        TransactionCurrency::whereIn('id', $found)->update(['enabled' => true]);

        return 0;
    }
}
