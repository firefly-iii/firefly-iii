<?php


/*
 * WebhookEnrichment.php
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

namespace FireflyIII\Support\JsonApi\Enrichments;

use FireflyIII\Enums\WebhookDelivery as WebhookDeliveryEnum;
use FireflyIII\Enums\WebhookResponse as WebhookResponseEnum;
use FireflyIII\Enums\WebhookTrigger as WebhookTriggerEnum;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookDelivery;
use FireflyIII\Models\WebhookResponse;
use FireflyIII\Models\WebhookTrigger;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class WebhookEnrichment implements EnrichmentInterface
{
    private Collection $collection;
    private User       $user; // @phpstan-ignore-line
    private UserGroup  $userGroup; // @phpstan-ignore-line
    private array      $ids          = [];
    private array      $deliveries   = [];
    private array      $responses    = [];
    private array      $triggers     = [];

    private array $webhookDeliveries = [];
    private array $webhookResponses  = [];
    private array $webhookTriggers   = [];

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        if ($this->collection->count() > 0) {
            $this->collectIds();
            $this->collectInfo();
            $this->collectWebhookInfo();
            $this->appendCollectedInfo();
        }

        return $this->collection;
    }

    public function enrichSingle(array|Model $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection()->push($model);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    private function collectIds(): void
    {
        /** @var Webhook $webhook */
        foreach ($this->collection as $webhook) {
            $this->ids[] = $webhook->id;
        }
        $this->ids = array_unique($this->ids);
    }

    private function collectInfo(): void
    {
        $all = WebhookDelivery::get();

        /** @var WebhookDelivery $item */
        foreach ($all as $item) {
            $this->deliveries[$item->id] = $item->key;
        }
        $all = WebhookResponse::get();

        /** @var WebhookResponse $item */
        foreach ($all as $item) {
            $this->responses[$item->id] = $item->key;
        }
        $all = WebhookTrigger::get();

        /** @var WebhookTrigger $item */
        foreach ($all as $item) {
            $this->triggers[$item->id] = $item->key;
        }

    }

    private function collectWebhookInfo(): void
    {
        $set = DB::table('webhook_webhook_delivery')->whereIn('webhook_id', $this->ids)->get(['webhook_id', 'webhook_delivery_id']);

        /** @var stdClass $item */
        foreach ($set as $item) {
            $id                             = $item->webhook_id;
            $deliveryId                     = $item->webhook_delivery_id;
            $this->webhookDeliveries[$id][] = WebhookDeliveryEnum::from($this->deliveries[$deliveryId])->name;
        }

        $set = DB::table('webhook_webhook_response')->whereIn('webhook_id', $this->ids)->get(['webhook_id', 'webhook_response_id']);

        /** @var stdClass $item */
        foreach ($set as $item) {
            $id                            = $item->webhook_id;
            $responseId                    = $item->webhook_response_id;
            $this->webhookResponses[$id][] = WebhookResponseEnum::from($this->responses[$responseId])->name;
        }

        $set = DB::table('webhook_webhook_trigger')->whereIn('webhook_id', $this->ids)->get(['webhook_id', 'webhook_trigger_id']);

        /** @var stdClass $item */
        foreach ($set as $item) {
            $id                           = $item->webhook_id;
            $triggerId                    = $item->webhook_trigger_id;
            $this->webhookTriggers[$id][] = WebhookTriggerEnum::from($this->triggers[$triggerId])->name;
        }
    }

    private function appendCollectedInfo(): void
    {
        $this->collection = $this->collection->map(function (Webhook $item) {
            $meta       = [
                'deliveries' => $this->webhookDeliveries[$item->id] ?? [],
                'responses'  => $this->webhookResponses[$item->id] ?? [],
                'triggers'   => $this->webhookTriggers[$item->id] ?? [],
            ];
            $item->meta = $meta;

            return $item;
        });
    }
}
