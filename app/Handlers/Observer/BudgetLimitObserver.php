<?php

/*
 * BudgetLimitObserver.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Observer;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Observers\RecalculatesAvailableBudgetsTrait;
use FireflyIII\Support\Singleton\PreferencesSingleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BudgetLimitObserver
{
    use RecalculatesAvailableBudgetsTrait;

    public function created(BudgetLimit $budgetLimit): void
    {
        Log::debug('Observe "created" of a budget limit.');
        $this->updatePrimaryCurrencyAmount($budgetLimit);
        $this->updateAvailableBudget($budgetLimit);


        // this is a lame trick to communicate with the observer.
        $singleton = PreferencesSingleton::getInstance();

        if (true === $singleton->getPreference('fire_webhooks_bl_store')) {

            $user   = $budgetLimit->budget->user;

            /** @var MessageGeneratorInterface $engine */
            $engine = app(MessageGeneratorInterface::class);
            $engine->setUser($user);
            $engine->setObjects(new Collection()->push($budgetLimit));
            $engine->setTrigger(WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT);
            $engine->generateMessages();

            Log::debug(sprintf('send event RequestedSendWebhookMessages from %s', __METHOD__));
            event(new RequestedSendWebhookMessages());
        }
    }

    private function updatePrimaryCurrencyAmount(BudgetLimit $budgetLimit): void
    {
        if (!Amount::convertToPrimary($budgetLimit->budget->user)) {
            // Log::debug('Do not update primary currency amount of the budget limit.');

            return;
        }
        $userCurrency               = app('amount')->getPrimaryCurrencyByUserGroup($budgetLimit->budget->user->userGroup);
        $budgetLimit->native_amount = null;
        if ($budgetLimit->transactionCurrency->id !== $userCurrency->id) {
            $converter                  = new ExchangeRateConverter();
            $converter->setUserGroup($budgetLimit->budget->user->userGroup);
            $converter->setIgnoreSettings(true);
            $budgetLimit->native_amount = $converter->convert($budgetLimit->transactionCurrency, $userCurrency, today(), $budgetLimit->amount);
        }
        $budgetLimit->saveQuietly();
        Log::debug('Budget limit primary currency amounts are updated.');
    }

    public function updated(BudgetLimit $budgetLimit): void
    {
        Log::debug('Observe "updated" of a budget limit.');
        $this->updatePrimaryCurrencyAmount($budgetLimit);
        $this->updateAvailableBudget($budgetLimit);

        // this is a lame trick to communicate with the observer.
        $singleton = PreferencesSingleton::getInstance();

        if (true === $singleton->getPreference('fire_webhooks_bl_update')) {
            $user   = $budgetLimit->budget->user;

            /** @var MessageGeneratorInterface $engine */
            $engine = app(MessageGeneratorInterface::class);
            $engine->setUser($user);
            $engine->setObjects(new Collection()->push($budgetLimit));
            $engine->setTrigger(WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT);
            $engine->generateMessages();

            Log::debug(sprintf('send event RequestedSendWebhookMessages from %s', __METHOD__));
            event(new RequestedSendWebhookMessages());
        }
    }
}
