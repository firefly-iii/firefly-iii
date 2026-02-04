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
    public function handle(DestroyedSingleTransactionGroup $event): void
    {
        $this->triggerWebhooks($event);
        $this->updateRunningBalance($event);
    }

    private function triggerWebhooks(DestroyedSingleTransactionGroup $destroyedGroupEvent): void
    {
        Log::debug('DestroyedTransactionGroup:triggerWebhooks');
        $group  = $destroyedGroupEvent->transactionGroup;
        $user   = $group->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($group));
        $engine->setTrigger(WebhookTrigger::DESTROY_TRANSACTION);
        $engine->generateMessages();
        Log::debug(sprintf('send event WebhookMessagesRequestSending from %s', __METHOD__));
        event(new WebhookMessagesRequestSending());
    }

    private function updateRunningBalance(DestroyedSingleTransactionGroup $event): void
    {
        if (false === FireflyConfig::get('use_running_balance', config('firefly.feature_flags.running_balance_column'))->data) {
            return;
        }
        Log::debug(__METHOD__);
        $group = $event->transactionGroup;
        foreach ($group->transactionJournals as $journal) {
            AccountBalanceCalculator::recalculateForJournal($journal);
        }
    }
}
