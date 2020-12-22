<?php
declare(strict_types=1);
/*
 * Webhook.php
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


use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Webhook
 *
 * @property int                                                                               $id
 * @property \Illuminate\Support\Carbon|null                                                   $created_at
 * @property \Illuminate\Support\Carbon|null                                                   $updated_at
 * @property \Illuminate\Support\Carbon|null                                                   $deleted_at
 * @property int                                                                               $user_id
 * @property bool                                                                              $active
 * @property int                                                                               $trigger
 * @property int                                                                               $response
 * @property int                                                                               $delivery
 * @property string                                                                            $url
 * @property-read User                                                                         $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\WebhookMessage[] $webhookMessages
 * @property-read int|null                                                                     $webhook_messages_count
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook newQuery()
 * @method static \Illuminate\Database\Query\Builder|Webhook onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook query()
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereTrigger($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Webhook whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|Webhook withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Webhook withoutTrashed()
 * @mixin \Eloquent
 */
class Webhook extends Model
{
    use SoftDeletes;

    // dont forget to update the config in firefly.php
    // triggers
    public const TRIGGER_STORE_TRANSACTION   = 100;
    public const TRIGGER_UPDATE_TRANSACTION  = 110;
    public const TRIGGER_DESTROY_TRANSACTION = 120;

    // actions
    public const RESPONSE_TRANSACTIONS = 200;
    public const RESPONSE_ACCOUNTS     = 210;
    public const RESPONSE_NONE         = 220;

    // delivery
    public const DELIVERY_JSON = 300;

    protected $fillable = ['active', 'trigger', 'response', 'delivery', 'user_id', 'url'];

    protected $casts
        = [
            'active'   => 'boolean',
            'trigger'  => 'integer',
            'response' => 'integer',
            'delivery' => 'integer',
        ];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Webhook
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Webhook
    {
        if (auth()->check()) {
            $budgetId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Webhook $webhook */
            $webhook = $user->webhooks()->find($budgetId);
            if (null !== $webhook) {
                return $webhook;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function webhookMessages(): HasMany
    {
        return $this->hasMany(WebhookMessage::class);
    }
}
