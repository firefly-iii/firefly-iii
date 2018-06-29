<?php
/**
 * RecurrenceDestroyService.php
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

use Exception;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use Log;

/**
 * @codeCoverageIgnore
 * Class RecurrenceDestroyService
 */
class RecurrenceDestroyService
{
    /**
     * @param Recurrence $recurrence
     */
    public function destroy(Recurrence $recurrence): void
    {
        try {
            // delete all meta data
            $recurrence->recurrenceMeta()->delete();

            // delete all transactions.
            /** @var RecurrenceTransaction $transaction */
            foreach($recurrence->recurrenceTransactions as $transaction) {
                $transaction->recurrenceTransactionMeta()->delete();
                $transaction->delete();
            }
            // delete all repetitions
            $recurrence->recurrenceRepetitions()->delete();

            // delete recurrence
            $recurrence->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::error(sprintf('Could not delete recurrence: %s', $e->getMessage())); // @codeCoverageIgnore
        }
    }

}
