<?php
/*
 * ProcessesNewTransactionGroup.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Events\Model\TransactionGroup\CreatedSingleTransactionGroup;
use Illuminate\Support\Facades\Log;

class ProcessesNewTransactionGroup
{
    public function handle(CreatedSingleTransactionGroup $event): void
    {
        Log::debug(sprintf('In ProcessesNewTransactionGroup::handle(#%d)', $event->transactionGroup->id));
        if (true === $event->flags->batchSubmission) {
            Log::debug(sprintf('Will do nothing for group #%d because it is part of a batch.', $event->transactionGroup->id));
            return;
        }
        Log::debug(sprintf('Will join group #%d with all other open transaction groups and process them.', $event->transactionGroup->id));
    }

}
