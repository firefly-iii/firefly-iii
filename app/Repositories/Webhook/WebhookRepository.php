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

use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class WebhookRepository
 */
class WebhookRepository implements WebhookRepositoryInterface
{
    private User $user;

    public function all(): Collection
    {
        return $this->user->webhooks()->get();
    }

    public function destroy(Webhook $webhook): void
    {
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
                static function (WebhookMessage $message) {
                    return $message->webhookAttempts()->count() <= 2;
                }
            )->splice(0, 3)
        ;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function store(array $data): Webhook
    {
        $secret   = \Str::random(24);
        $fullData = [
            'user_id'       => $this->user->id,
            'user_group_id' => $this->user->user_group_id,
            'active'        => $data['active'] ?? false,
            'title'         => $data['title'] ?? null,
            'trigger'       => $data['trigger'],
            'response'      => $data['response'],
            'delivery'      => $data['delivery'],
            'secret'        => $secret,
            'url'           => $data['url'],
        ];

        return Webhook::create($fullData);
    }

    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->active   = $data['active'] ?? $webhook->active;
        $webhook->trigger  = $data['trigger'] ?? $webhook->trigger;
        $webhook->response = $data['response'] ?? $webhook->response;
        $webhook->delivery = $data['delivery'] ?? $webhook->delivery;
        $webhook->title    = $data['title'] ?? $webhook->title;
        $webhook->url      = $data['url'] ?? $webhook->url;

        if (true === $data['secret']) {
            $secret          = \Str::random(24);
            $webhook->secret = $secret;
        }

        $webhook->save();

        return $webhook;
    }
}
