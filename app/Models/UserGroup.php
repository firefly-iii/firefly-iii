<?php

/*
 * UserGroup.php
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

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * Class UserGroup
 *
 * @property int                               $id
 * @property Carbon|null                       $created_at
 * @property Carbon|null                       $updated_at
 * @property string|null                       $deleted_at
 * @property string                            $title
 * @property-read Collection|GroupMembership[] $groupMemberships
 * @property-read int|null                     $group_memberships_count
 * @method static Builder|UserGroup newModelQuery()
 * @method static Builder|UserGroup newQuery()
 * @method static Builder|UserGroup query()
 * @method static Builder|UserGroup whereCreatedAt($value)
 * @method static Builder|UserGroup whereDeletedAt($value)
 * @method static Builder|UserGroup whereId($value)
 * @method static Builder|UserGroup whereTitle($value)
 * @method static Builder|UserGroup whereUpdatedAt($value)
 * @property-read Collection<int, Account>     $accounts
 * @property-read int|null                     $accounts_count
 * @mixin Eloquent
 */
class UserGroup extends Model
{
    protected $fillable = ['title'];

    /**
     * Link to accounts.
     *
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Link to bills.
     *
     * @return HasMany
     */
    public function availableBudgets(): HasMany
    {
        return $this->hasMany(AvailableBudget::class);
    }

    /**
     * Link to bills.
     *
     * @return HasMany
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Link to budgets.
     *
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     *
     * @return HasMany
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * Link to piggy banks.
     *
     * @return HasManyThrough
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough(PiggyBank::class, Account::class);
    }

    /**
     * Link to transaction journals.
     *
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }
}
