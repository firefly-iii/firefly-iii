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
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Handlers\ExchangeRate\ConversionParameters;
use FireflyIII\Handlers\ExchangeRate\ConvertsAmountToPrimaryAmount;
use FireflyIII\Models\BudgetLimit;
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
        $this->sendWebhookMessages('fire_webhooks_bl_store', WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT, $budgetLimit);
    }

    public function updated(BudgetLimit $budgetLimit): void
    {
        Log::debug('Observe "updated" of a budget limit.');
        $this->updatePrimaryCurrencyAmount($budgetLimit);
        $this->updateAvailableBudget($budgetLimit);
        $this->sendWebhookMessages('fire_webhooks_bl_update', WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT, $budgetLimit);
    }

    private function sendWebhookMessages(string $key, WebhookTrigger $trigger, BudgetLimit $budgetLimit): void
    {
        // this is a lame trick to communicate with the observer.
        $singleton = PreferencesSingleton::getInstance();

        if (true === $singleton->getPreference($key)) {
            $user = $budgetLimit->budget->user;

            /** @var MessageGeneratorInterface $engine */
            $engine = app(MessageGeneratorInterface::class);
            $engine->setUser($user);
            $engine->setObjects(new Collection()->push($budgetLimit));
            $engine->setTrigger($trigger);
            $engine->generateMessages();

            Log::debug(sprintf('send event WebhookMessagesRequestSending from %s', __METHOD__));
            event(new WebhookMessagesRequestSending());
        }
    }

    private function updatePrimaryCurrencyAmount(BudgetLimit $budgetLimit): void
    {
        $params                     = new ConversionParameters();
        $params->user               = $budgetLimit->budget->user;
        $params->model              = $budgetLimit;
        $params->originalCurrency   = $budgetLimit->transactionCurrency;
        $params->amountField        = 'amount';
        $params->primaryAmountField = 'native_amount';
        ConvertsAmountToPrimaryAmount::convert($params);
    }
}
