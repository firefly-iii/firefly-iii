<?php
/**
 * TransactionGroupDestroyService.php
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

declare(strict_types=1);

namespace FireflyIII\Services\Internal\Destroy;

use Exception;
use FireflyIII\Models\TransactionGroup;

/**
 * Class TransactionGroupDestroyService
 * @codeCoverageIgnore
 */
class TransactionGroupDestroyService
{

    /**
     * @param TransactionGroup $transactionGroup
     */
    public function destroy(TransactionGroup $transactionGroup): void
    {
        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        foreach ($transactionGroup->transactionJournals as $journal) {
            $service->destroy($journal);
        }
        try {
            $transactionGroup->delete();
        } catch (Exception $e) {
            app('log')->error(sprintf('Could not delete transaction group: %s', $e->getMessage())); // @codeCoverageIgnore
        }
    }

}
