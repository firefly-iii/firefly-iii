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
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Delete recurrence.
     *
     * @param Recurrence $recurrence
     *
     */
    public function destroy(Recurrence $recurrence): void
    {
        try {
            // delete all meta data
            $recurrence->recurrenceMeta()->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::info(sprintf('Could not delete recurrence meta: %s', $e->getMessage())); // @codeCoverageIgnore
        }
        // delete all transactions.
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions as $transaction) {
            $transaction->recurrenceTransactionMeta()->delete();
            try {
                $transaction->delete();
            } catch (Exception $e) { // @codeCoverageIgnore
                Log::info(sprintf('Could not delete recurrence transaction: %s', $e->getMessage())); // @codeCoverageIgnore
            }
        }
        // delete all repetitions
        $recurrence->recurrenceRepetitions()->delete();

        // delete recurrence
        try {
            $recurrence->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::info(sprintf('Could not delete recurrence: %s', $e->getMessage())); // @codeCoverageIgnore
        }
    }

    /**
     * Delete recurrence by ID
     *
     * @param int $recurrenceId
     */
    public function destroyById(int $recurrenceId): void
    {
        $recurrence = Recurrence::find($recurrenceId);
        if (null === $recurrence) {
            return;
        }
        $this->destroy($recurrence);

    }

}
