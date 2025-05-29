<?php

/*
 * GroupMembership.php
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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMembership extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;

    protected $fillable = ['user_id', 'user_group_id', 'user_role_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    protected function userRoleId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
    protected function casts(): array
    {
        return [
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
            'user_id'       => 'integer',
            'user_group_id' => 'integer',
        ];
    }
}
