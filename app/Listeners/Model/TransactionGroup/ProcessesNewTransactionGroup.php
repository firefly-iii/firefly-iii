<?php

declare(strict_types=1);

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

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\CreatedSingleTransactionGroup;
use FireflyIII\Events\Model\TransactionGroup\UserRequestedBatchProcessing;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessesNewTransactionGroup implements ShouldQueue
{
    use SupportsGroupProcessingTrait;

    public function handle(CreatedSingleTransactionGroup|UserRequestedBatchProcessing $event): void
    {
        Log::debug(sprintf('Running event handler for %s', get_class($event)));

        $setting    = FireflyConfig::get('enable_batch_processing', false)->data;
        if (true === $event->flags->batchSubmission && true === $setting) {
            Log::debug('Will do nothing for event because it is part of a batch.');

            return;
        }
        $repository = app(JournalRepositoryInterface::class);
        $journals   = $event->objects->transactionJournals->merge($repository->getAllUncompletedJournals());

        Log::debug(sprintf('Transaction journal count is %d', $journals->count()));
        if (!$event->flags->applyRules) {
            Log::debug(sprintf('Will NOT process rules for %d journal(s)', $journals->count()));
        }
        if (!$event->flags->recalculateCredit) {
            Log::debug(sprintf('Will NOT recalculate credit for %d journal(s)', $journals->count()));
        }
        if (!$event->flags->fireWebhooks) {
            Log::debug(sprintf('Will NOT fire webhooks for %d journal(s)', $journals->count()));
        }

        if ($event->flags->applyRules) {
            $this->processRules($journals, 'store-journal');
        }
        if ($event->flags->recalculateCredit) {
            $this->recalculateCredit($event->objects->accounts);
        }
        if ($event->flags->fireWebhooks) {
            $this->createWebhookMessages($event->objects->transactionGroups, WebhookTrigger::STORE_TRANSACTION);
        }
        $this->removePeriodStatistics($event->objects);
        $this->recalculateRunningBalance($event->objects);
        $repository->markAsCompleted($journals);
    }
}
