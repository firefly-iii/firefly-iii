<?php
/*
 * WebhookRepositoryInterface.php
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

/**
 * Interface WebhookRepositoryInterface
 */
interface WebhookRepositoryInterface
{
    /**
     * Return all webhooks.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Set user.
     *
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * @param array $data
     *
     * @return Webhook
     */
    public function store(array $data): Webhook;

    /**
     * @param Webhook $webhook
     * @param array   $data
     *
     * @return Webhook
     */
    public function update(Webhook $webhook, array $data): Webhook;

    /**
     * @param Webhook $webhook
     */
    public function destroy(Webhook $webhook): void;

}