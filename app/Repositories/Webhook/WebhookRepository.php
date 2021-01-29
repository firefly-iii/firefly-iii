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
/*
 * WebhookRepository.php
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

namespace FireflyIII\Repositories\Webhook;

use FireflyIII\Models\Webhook;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Str;

/**
 * Class WebhookRepository
 */
class WebhookRepository implements WebhookRepositoryInterface
{
    private User $user;

    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return $this->user->webhooks()->get();
    }

    /**
     * @inheritDoc
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function store(array $data): Webhook
    {
        $secret   = $random = Str::random(24);
        $fullData = [
            'user_id'  => $this->user->id,
            'active'   => $data['active'] ?? false,
            'title'    => $data['title'] ?? null,
            'trigger'  => $data['trigger'],
            'response' => $data['response'],
            'delivery' => $data['delivery'],
            'secret'   => $secret,
            'url'      => $data['url'],
        ];

        return Webhook::create($fullData);
    }

    /**
     * @inheritDoc
     */
    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->active   = $data['active'] ?? $webhook->active;
        $webhook->trigger  = $data['trigger'] ?? $webhook->trigger;
        $webhook->response = $data['response'] ?? $webhook->response;
        $webhook->delivery = $data['delivery'] ?? $webhook->delivery;
        $webhook->url      = $data['url'] ?? $webhook->url;

        if (array_key_exists('secret', $data) && null !== $data['secret']) {
            $secret          = $random = Str::random(24);
            $webhook->secret = $secret;
        }

        $webhook->save();

        return $webhook;
    }

    /**
     * @inheritDoc
     */
    public function destroy(Webhook $webhook): void
    {
        $webhook->delete();
    }
}
