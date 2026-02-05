<?php

declare(strict_types=1);

/*
 * ProcessesDestroyedTransactionGroup.php
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
use FireflyIII\Events\Model\TransactionGroup\DestroyedSingleTransactionGroup;
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessesDestroyedTransactionGroup implements ShouldQueue
{
    use SupportsGroupProcessingTrait;
    public function handle(DestroyedSingleTransactionGroup $event): void
    {
        Log::debug(sprintf('User called %s', get_class($event)));

        if (!$event->flags->recalculateCredit) {
            Log::debug(sprintf('Will NOT recalculate credit for %d journal(s)', $event->objects->transactionJournals->count()));
        }
        if (!$event->flags->fireWebhooks) {
            Log::debug(sprintf('Will NOT fire webhooks for %d journal(s)', $event->objects->transactionJournals->count()));
        }

        if ($event->flags->recalculateCredit) {
            $this->recalculateCredit($event->objects->accounts);
        }
        if ($event->flags->fireWebhooks) {
            $this->fireWebhooks($event->objects->transactionGroups, WebhookTrigger::DESTROY_TRANSACTION);
        }
        $this->removePeriodStatistics($event->objects);
        $this->recalculateRunningBalance($event->objects);
    }
}
