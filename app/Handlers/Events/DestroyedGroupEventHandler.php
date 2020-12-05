<?php
/*
 * DestroyedGroupEventHandler.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\DestroyedTransactionGroup;
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Webhook;
use Illuminate\Support\Collection;
use Log;

/**
 * Class DestroyedGroupEventHandler
 */
class DestroyedGroupEventHandler
{
    /**
     * @param DestroyedTransactionGroup $destroyedGroupEvent
     */
    public function triggerWebhooks(DestroyedTransactionGroup $destroyedGroupEvent): void
    {
        Log::debug('DestroyedTransactionGroup:triggerWebhooks');
        $group = $destroyedGroupEvent->transactionGroup;
        $user  = $group->user;
        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection([$group]));
        $engine->setTrigger(Webhook::TRIGGER_DESTROY_TRANSACTION);
        $engine->generateMessages();

        event(new RequestedSendWebhookMessages);
    }
}