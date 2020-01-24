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
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enables all currencies in use.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:enable-currencies';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $start = microtime(true);
        $found = [];
        // get all meta entries
        /** @var Collection $meta */
        $meta = AccountMeta::where('name', 'currency_id')->groupBy('data')->get(['data']);
        foreach ($meta as $entry) {
            $found[] = (int)$entry->data;
        }

        // get all from journals:
        /** @var Collection $journals */
        $journals = TransactionJournal::groupBy('transaction_currency_id')->get(['transaction_currency_id']);
        foreach ($journals as $entry) {
            $found[] = (int)$entry->transaction_currency_id;
        }

        // get all from transactions
        /** @var Collection $transactions */
        $transactions = Transaction::groupBy('transaction_currency_id')->get(['transaction_currency_id']);
        foreach ($transactions as $entry) {
            $found[] = (int)$entry->transaction_currency_id;
        }

        // get all from budget limits
        /** @var Collection $limits */
        $limits = BudgetLimit::groupBy('transaction_currency_id')->get(['transaction_currency_id']);
        foreach ($limits as $entry) {
            $found[] = (int)$entry->transaction_currency_id;
        }

        $found = array_unique($found);
        $this->info(sprintf('%d different currencies are currently in use.', count($found)));

        $disabled = TransactionCurrency::whereIn('id', $found)->where('enabled', false)->count();
        if ($disabled > 0) {
            $this->info(sprintf('%d were (was) still disabled. This has been corrected.', $disabled));
        }
        if (0 === $disabled) {
            $this->info('All currencies are correctly enabled or disabled.');
        }
        TransactionCurrency::whereIn('id', $found)->update(['enabled' => true]);

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified currencies in %s seconds.', $end));

        return 0;
    }
}
