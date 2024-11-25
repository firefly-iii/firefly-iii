<?php

/**
 * TransactionGroupDestroyService.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Services\Internal\Destroy;

use FireflyIII\Events\DestroyedTransactionGroup;
use FireflyIII\Models\TransactionGroup;

/**
 * Class TransactionGroupDestroyService
 */
class TransactionGroupDestroyService
{
    public function destroy(TransactionGroup $transactionGroup): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        foreach ($transactionGroup->transactionJournals as $journal) {
            $service->destroy($journal);
        }
        $transactionGroup->delete();
        // trigger just after destruction
        event(new DestroyedTransactionGroup($transactionGroup));
    }
}
