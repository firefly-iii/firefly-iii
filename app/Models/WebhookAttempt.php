<?php

/*
 * WebhookAttempt.php
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
 * WebhookAttempt.php
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
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WebhookAttempt
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int $webhook_message_id
 * @property int $status_code
 * @property string|null $logs
 * @property string|null $response
 * @property-read \FireflyIII\Models\WebhookMessage $webhookMessage
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereLogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebhookAttempt whereWebhookMessageId($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Query\Builder|WebhookAttempt onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|WebhookAttempt withTrashed()
 * @method static \Illuminate\Database\Query\Builder|WebhookAttempt withoutTrashed()
 */
class WebhookAttempt extends Model
{
    use SoftDeletes;
    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function webhookMessage(): BelongsTo
    {
        return $this->belongsTo(WebhookMessage::class);
    }

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return WebhookAttempt
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): WebhookAttempt
    {
        if (auth()->check()) {
            $attemptId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var WebhookAttempt $attempt */
            $attempt = self::find($attemptId);
            if (null !== $attempt) {
                if($attempt->webhookMessage->webhook->user_id === $user->id) {
                    return $attempt;
                }
            }
        }
        throw new NotFoundHttpException;
    }
}
