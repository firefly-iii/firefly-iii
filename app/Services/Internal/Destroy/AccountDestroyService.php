<?php
/**
 * AccountDestroyService.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Services\Internal\Destroy;

use DB;
use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class AccountDestroyService
 */
class AccountDestroyService
{
    /**
     * @param Account      $account
     * @param Account|null $moveTo
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function destroy(Account $account, ?Account $moveTo): void
    {
        if (null !== $moveTo) {
            DB::table('transactions')->where('account_id', $account->id)->update(['account_id' => $moveTo->id]);
        }
        $service = app(JournalDestroyService::class);

        Log::debug('Now trigger account delete response #' . $account->id);
        /** @var Transaction $transaction */
        foreach ($account->transactions()->get() as $transaction) {
            Log::debug('Now at transaction #' . $transaction->id);
            /** @var TransactionJournal $journal */
            $journal = $transaction->transactionJournal()->first();
            if (null !== $journal) {
                Log::debug('Call for deletion of journal #' . $journal->id);
                /** @var JournalDestroyService $service */

                $service->destroy($journal);
            }
        }
        try {
            $account->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::error(sprintf('Could not delete account: %s', $e->getMessage())); // @codeCoverageIgnore
        }
    }

}
