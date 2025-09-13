<?php


/*
 * UpgradesWebhooks.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\WebhookDelivery;
use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookDelivery as WebhookDeliveryModel;
use FireflyIII\Models\WebhookResponse as WebhookResponseModel;
use FireflyIII\Models\WebhookTrigger as WebhookTriggerModel;
use Illuminate\Console\Command;

class UpgradesWebhooks extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '640_upgrade_webhooks';
    protected $description          = 'Upgrade webhooks so they can handle multiple triggers.';
    protected $signature            = 'upgrade:640-upgrade-webhooks {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        $this->upgradeWebhooks();
        $this->markAsExecuted();
        $this->friendlyPositive('Upgraded webhooks.');

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    private function upgradeWebhooks(): void
    {
        $set = Webhook::where('delivery', '>', 1)->orWhere('trigger', '>', 1)->orWhere('response', '>', 1)->get();

        /** @var Webhook $webhook */
        foreach ($set as $webhook) {
            $this->upgradeWebhook($webhook);
        }
    }

    private function upgradeWebhook(Webhook $webhook): void
    {
        $delivery          = WebhookDelivery::tryFrom((int)$webhook->delivery);
        $response          = WebhookResponse::tryFrom((int)$webhook->response);
        $trigger           = WebhookTrigger::tryFrom((int)$webhook->trigger);
        if (null === $delivery || null === $response || null === $trigger) {
            $this->friendlyError(sprintf('[a] Webhook #%d has an invalid delivery, response or trigger value. Will not upgrade.', $webhook->id));

            return;
        }
        $deliveryModel     = WebhookDeliveryModel::where('key', $delivery->value)->first();
        $responseModel     = WebhookResponseModel::where('key', $response->value)->first();
        $triggerModel      = WebhookTriggerModel::where('key', $trigger->value)->first();
        if (null === $deliveryModel || null === $responseModel || null === $triggerModel) {
            $this->friendlyError(sprintf('[b] Webhook #%d has an invalid delivery, response or trigger model. Will not upgrade.', $webhook->id));

            return;
        }
        $webhook->webhookDeliveries()->attach([$deliveryModel->id]);
        $webhook->webhookResponses()->attach([$responseModel->id]);
        $webhook->webhookTriggers()->attach([$triggerModel->id]);
        $webhook->delivery = 1;
        $webhook->response = 1;
        $webhook->trigger  = 1;
        $webhook->save();
        $this->friendlyPositive(sprintf('Webhook #%d upgraded.', $webhook->id));
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
