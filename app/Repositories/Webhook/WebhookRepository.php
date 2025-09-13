<?php

/*
 * WebhookRepository.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Repositories\Webhook;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookDelivery;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Models\WebhookResponse;
use FireflyIII\Models\WebhookTrigger;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class WebhookRepository
 */
class WebhookRepository implements WebhookRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    public function all(): Collection
    {
        return $this->user->webhooks()
            // only get upgraded webhooks
            ->where('delivery', 1)
            ->where('response', 1)
            ->where('trigger', 1)
            ->get()
        ;
    }

    public function destroy(Webhook $webhook): void
    {
        // force delete all messages and attempts:
        $webhook->webhookMessages()->delete();

        $webhook->delete();
    }

    public function destroyAttempt(WebhookAttempt $attempt): void
    {
        $attempt->delete();
    }

    public function destroyMessage(WebhookMessage $message): void
    {
        $message->delete();
    }

    public function getAttempts(WebhookMessage $webhookMessage): Collection
    {
        return $webhookMessage->webhookAttempts()->orderBy('created_at', 'DESC')->get(['webhook_attempts.*']);
    }

    public function getMessages(Webhook $webhook): Collection
    {
        return $webhook->webhookMessages()
            ->orderBy('created_at', 'DESC')
            ->get(['webhook_messages.*'])
        ;
    }

    public function getReadyMessages(Webhook $webhook): Collection
    {
        return $webhook->webhookMessages()
            ->where('webhook_messages.sent', 0)
            ->where('webhook_messages.errored', 0)
            ->get(['webhook_messages.*'])
            ->filter(
                static fn (WebhookMessage $message) // @phpstan-ignore-line
                => $message->webhookAttempts()->count() <= 2
            )->splice(0, 3)
        ;
    }

    public function store(array $data): Webhook
    {
        $secret     = Str::random(24);
        $fullData   = [
            'user_id'       => $this->user->id,
            'user_group_id' => $this->user->user_group_id,
            'active'        => $data['active'] ?? false,
            'title'         => $data['title'] ?? null,
            //            'trigger'       => $data['trigger'],
            //            'response'      => $data['response'],
            //            'delivery'      => $data['delivery'],
            'trigger'       => 1,
            'response'      => 1,
            'delivery'      => 1,
            'secret'        => $secret,
            'url'           => $data['url'],
        ];

        /** @var Webhook $webhook */
        $webhook    = Webhook::create($fullData);
        $triggers   = new Collection();
        $responses  = new Collection();
        $deliveries = new Collection();

        foreach ($data['triggers'] as $trigger) {
            // get the relevant ID:
            $object = WebhookTrigger::where('title', $trigger)->first();
            if (null === $object) {
                throw new FireflyException(sprintf('Could not find webhook trigger with title "%s".', $trigger));
            }
            $triggers->push($object);
        }
        $webhook->webhookTriggers()->saveMany($triggers);

        foreach ($data['responses'] as $response) {
            // get the relevant ID:
            $object = WebhookResponse::where('title', $response)->first();
            if (null === $object) {
                throw new FireflyException(sprintf('Could not find webhook response with title "%s".', $response));
            }
            $responses->push($object);
        }
        $webhook->webhookResponses()->saveMany($responses);

        foreach ($data['deliveries'] as $delivery) {
            // get the relevant ID:
            $object = WebhookDelivery::where('title', $delivery)->first();
            if (null === $object) {
                throw new FireflyException(sprintf('Could not find webhook delivery with title "%s".', $delivery));
            }
            $deliveries->push($object);
        }
        $webhook->webhookDeliveries()->saveMany($deliveries);

        return $webhook;
    }

    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->active = $data['active'] ?? $webhook->active;
        $webhook->title  = $data['title'] ?? $webhook->title;
        $webhook->url    = $data['url'] ?? $webhook->url;

        if (array_key_exists('secret', $data) && true === $data['secret']) {
            $secret          = Str::random(24);
            $webhook->secret = $secret;
        }

        $webhook->save();

        $triggers        = new Collection();
        $responses       = new Collection();
        $deliveries      = new Collection();

        foreach ($data['triggers'] as $trigger) {
            // get the relevant ID:
            $object = WebhookTrigger::where('title', $trigger)->first();
            if (null === $object) {
                throw new FireflyException(sprintf('Could not find webhook trigger with title "%s".', $trigger));
            }
            $triggers->push($object);
        }
        $webhook->webhookTriggers()->sync($triggers);

        foreach ($data['responses'] as $response) {
            // get the relevant ID:
            $object = WebhookResponse::where('title', $response)->first();
            if (null === $object) {
                throw new FireflyException(sprintf('Could not find webhook response with title "%s".', $response));
            }
            $responses->push($object);
        }
        $webhook->webhookResponses()->sync($responses);

        foreach ($data['deliveries'] as $delivery) {
            // get the relevant ID:
            $object = WebhookDelivery::where('title', $delivery)->first();
            if (null === $object) {
                throw new FireflyException(sprintf('Could not find webhook delivery with title "%s".', $delivery));
            }
            $deliveries->push($object);
        }
        $webhook->webhookDeliveries()->sync($deliveries);

        return $webhook;
    }
}
