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

use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class GroupMembership
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int $user_id
 * @property int $user_group_id
 * @property int $user_role_id
 * @property-read User $user
 * @property-read \FireflyIII\Models\UserGroup $userGroup
 * @property-read \FireflyIII\Models\UserRole $userRole
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership query()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereUserGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupMembership whereUserRoleId($value)
 * @mixin \Eloquent
 */
class GroupMembership extends Model
{
    protected $fillable = ['user_id', 'user_group_id', 'user_role_id'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /**
     * @return BelongsTo
     */
    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }
}
