<?php

/*
 * InvitedUser.php
 * Copyright (c) 2022 james@firefly-iii.org
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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperInvitedUser
 */
class InvitedUser extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;

    protected $casts
                        = [
            'expires'  => 'datetime',
            'redeemed' => 'boolean',
        ];
    protected $fillable = ['user_id', 'email', 'invite_code', 'expires', 'expires_tz', 'redeemed'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $attemptId = (int)$value;

            /** @var null|InvitedUser $attempt */
            $attempt   = self::find($attemptId);
            if (null !== $attempt) {
                return $attempt;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
