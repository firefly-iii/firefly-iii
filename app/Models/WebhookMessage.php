<?php
/*
 * WebhookMessage.php
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

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FireflyIII\Models\WebhookMessage
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int $webhook_id
 * @property bool $sent
 * @property bool $errored
 * @property int $attempts
 * @property string $uuid
 * @property array $message
 * @property array|null $logs
 * @property-read \FireflyIII\Models\Webhook $webhook
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereErrored($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereLogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookMessage whereWebhookId($value)
 * @mixin \Eloquent
 */
class WebhookMessage extends Model
{

    protected $casts
        = [
            'sent'    => 'boolean',
            'errored' => 'boolean',
            'uuid'    => 'string',
            'message' => 'json',
            'logs' => 'json',
        ];

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function webhookAttempts(): HasMany
    {
        return $this->hasMany(WebhookAttempt::class);
    }


}