<?php
/*
 * UserRole.php
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

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class UserRole
 */
class UserRole extends Model
{
    public const READ_ONLY           = 'ro';
    public const CHANGE_TRANSACTIONS = 'change_tx';
    public const CHANGE_RULES        = 'change_rules';
    public const CHANGE_PIGGY_BANKS  = 'change_piggies';
    public const CHANGE_REPETITIONS  = 'change_reps';
    public const VIEW_REPORTS        = 'view_reports';
    public const FULL                = 'full';
    protected $fillable = ['title'];

    /**
     * @codeCoverageIgnore
     *
     * @return HasMany
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }
}