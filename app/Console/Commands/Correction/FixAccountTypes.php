<?php
/**
 * FixAccountTypes.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;

/**
 * Class FixAccountTypes
 */
class FixAccountTypes extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure all journals have the correct from/to account types.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-account-types';
    /** @var array */
    private $expected;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->expected = config('firefly.source_dests');
        $journals       = TransactionJournal::get();
        foreach ($journals as $journal) {
            $this->inspectJournal($journal);
        }

        return 0;
    }

    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        return $journal->transactions()->where('amount', '>', 0)->first()->account;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        return $journal->transactions()->where('amount', '<', 0)->first()->account;
    }

    /**
     * @param TransactionJournal $journal
     */
    private function inspectJournal(TransactionJournal $journal): void
    {
        $count = $journal->transactions()->count();
        if (2 !== $count) {
            $this->info(sprintf('Cannot inspect journal #%d because it does not have 2 transactions, but %d', $journal->id, $count));

            return;
        }
        $type                   = $journal->transactionType->type;
        $sourceAccount          = $this->getSourceAccount($journal);
        $sourceAccountType      = $sourceAccount->accountType->type;
        $destinationAccount     = $this->getDestinationAccount($journal);
        $destinationAccountType = $destinationAccount->accountType->type;
        if (!isset($this->expected[$type])) {
            $this->info(sprintf('No source/destination info for transaction type %s.', $type));

            return;
        }
        if (!isset($this->expected[$type][$sourceAccountType])) {
            $this->info(sprintf('The source of %s #%d cannot be of type "%s".', $type, $journal->id, $sourceAccountType));
            $this->info(sprintf('The destination of %s #%d probably cannot be of type "%s".', $type, $journal->id, $destinationAccountType));

            // TODO think of a way to fix the problem.
            return;
        }
        $expectedTypes = $this->expected[$type][$sourceAccountType];
        if (!\in_array($destinationAccountType, $expectedTypes, true)) {
            $this->info(sprintf('The destination of %s #%d cannot be of type "%s".', $type, $journal->id, $destinationAccountType));
            // TODO think of a way to fix the problem.
        }
    }
}
