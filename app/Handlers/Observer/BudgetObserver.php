<?php

/*
 * BudgetObserver.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Observer;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Support\Observers\RecalculatesAvailableBudgetsTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class BudgetObserver
 */
class BudgetObserver
{
    use RecalculatesAvailableBudgetsTrait;

    public function created(Budget $budget): void
    {
        Log::debug(sprintf('Observe "created" of budget #%d ("%s").', $budget->id, $budget->name));

        // fire event.
        $user   = $budget->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($budget));
        $engine->setTrigger(WebhookTrigger::STORE_BUDGET);
        $engine->generateMessages();
        Log::debug(sprintf('send event RequestedSendWebhookMessages from %s', __METHOD__));
        event(new RequestedSendWebhookMessages());
    }

    public function updated(Budget $budget): void
    {
        Log::debug(sprintf('Observe "updated" of budget #%d ("%s").', $budget->id, $budget->name));
        $user   = $budget->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($budget));
        $engine->setTrigger(WebhookTrigger::UPDATE_BUDGET);
        $engine->generateMessages();
        Log::debug(sprintf('send event RequestedSendWebhookMessages from %s', __METHOD__));
        event(new RequestedSendWebhookMessages());
    }

    public function deleting(Budget $budget): void
    {
        Log::debug('Observe "deleting" of a budget.');

        $user         = $budget->user;

        /** @var MessageGeneratorInterface $engine */
        $engine       = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($budget));
        $engine->setTrigger(WebhookTrigger::DESTROY_BUDGET);
        $engine->generateMessages();
        Log::debug(sprintf('send event RequestedSendWebhookMessages from %s', __METHOD__));
        event(new RequestedSendWebhookMessages());

        $repository   = app(AttachmentRepositoryInterface::class);
        $repository->setUser($budget->user);

        /** @var Attachment $attachment */
        foreach ($budget->attachments()->get() as $attachment) {
            $repository->destroy($attachment);
        }
        $budgetLimits = $budget->budgetlimits()->get();

        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            // this loop exists so several events are fired.
            $copy     = clone $budgetLimit;
            $copy->id = 0;
            $this->updateAvailableBudget($copy);
            $budgetLimit->deleteQuietly(); // delete is quietly when in a loop.
        }

        $budget->notes()->delete();
        $budget->autoBudgets()->delete();

        // recalculate available budgets.
    }
}
