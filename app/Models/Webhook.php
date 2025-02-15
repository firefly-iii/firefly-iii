<?php

/*
 * Webhook.php
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

namespace FireflyIII\Models;

use FireflyIII\Enums\WebhookDelivery;
use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Webhook extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
                        = [
            'active'   => 'boolean',
            'trigger'  => 'integer',
            'response' => 'integer',
            'delivery' => 'integer',
            'user_id'                => 'integer',
            'user_group_id'                => 'integer',
        ];
    protected $fillable = ['active', 'trigger', 'response', 'delivery', 'user_id', 'user_group_id', 'url', 'title', 'secret'];

    public static function getDeliveries(): array
    {
        $array = [];
        $set   = WebhookDelivery::cases();
        foreach ($set as $item) {
            $array[$item->value] = $item->name;
        }

        return $array;
    }

    public static function getDeliveriesForValidation(): array
    {
        $array = [];
        $set   = WebhookDelivery::cases();
        foreach ($set as $item) {
            $array[$item->name]  = $item->value;
            $array[$item->value] = $item->value;
        }

        return $array;
    }

    public static function getResponses(): array
    {
        $array = [];
        $set   = WebhookResponse::cases();
        foreach ($set as $item) {
            $array[$item->value] = $item->name;
        }

        return $array;
    }

    public static function getResponsesForValidation(): array
    {
        $array = [];
        $set   = WebhookResponse::cases();
        foreach ($set as $item) {
            $array[$item->name]  = $item->value;
            $array[$item->value] = $item->value;
        }

        return $array;
    }

    public static function getTriggers(): array
    {
        $array = [];
        $set   = WebhookTrigger::cases();
        foreach ($set as $item) {
            $array[$item->value] = $item->name;
        }

        return $array;
    }

    public static function getTriggersForValidation(): array
    {
        $array = [];
        $set   = WebhookTrigger::cases();
        foreach ($set as $item) {
            $array[$item->name]  = $item->value;
            $array[$item->value] = $item->value;
        }

        return $array;
    }

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $webhookId = (int) $value;

            /** @var User $user */
            $user      = auth()->user();

            /** @var null|Webhook $webhook */
            $webhook   = $user->webhooks()->find($webhookId);
            if (null !== $webhook) {
                return $webhook;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhookMessages(): HasMany
    {
        return $this->hasMany(WebhookMessage::class);
    }

    protected function casts(): array
    {
        return [
            //            'delivery' => WebhookDelivery::class,
            //            'response' => WebhookResponse::class,
            //            'trigger'  => WebhookTrigger::class,
        ];
    }
}
