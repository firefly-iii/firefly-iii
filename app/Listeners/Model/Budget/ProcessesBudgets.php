<?php

declare(strict_types=1);

/*
 * ProcessesBudgets.php
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

namespace FireflyIII\Listeners\Model\Budget;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\Budget\CreatedBudget;
use FireflyIII\Events\Model\Budget\DestroyingBudget;
use FireflyIII\Events\Model\Budget\UpdatedBudget;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessesBudgets implements ShouldQueue
{
    public function handle(CreatedBudget|DestroyingBudget|UpdatedBudget $event): void
    {
        Log::debug(sprintf('Will now handle %s', get_class($event)));
        $trigger = WebhookTrigger::STORE_BUDGET;
        if ($event instanceof DestroyingBudget) {
            $trigger = WebhookTrigger::DESTROY_BUDGET;
        }
        if ($event instanceof UpdatedBudget) {
            $trigger = WebhookTrigger::UPDATE_BUDGET;
        }

        /** @var MessageGeneratorInterface $engine */
        $engine  = app(MessageGeneratorInterface::class);
        $engine->setUser($event->budget->user);
        $engine->setObjects(new Collection()->push($event->budget));
        $engine->setTrigger($trigger);
        $engine->generateMessages();
    }
}
