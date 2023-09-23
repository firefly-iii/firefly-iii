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
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserGroup
 *
 * @property int                                      $id
 * @property Carbon|null                              $created_at
 * @property Carbon|null                              $updated_at
 * @property string|null                              $deleted_at
 * @property string                                   $title
 * @property-read Collection|GroupMembership[]        $groupMemberships
 * @property-read int|null                            $group_memberships_count
 * @method static Builder|UserGroup newModelQuery()
 * @method static Builder|UserGroup newQuery()
 * @method static Builder|UserGroup query()
 * @method static Builder|UserGroup whereCreatedAt($value)
 * @method static Builder|UserGroup whereDeletedAt($value)
 * @method static Builder|UserGroup whereId($value)
 * @method static Builder|UserGroup whereTitle($value)
 * @method static Builder|UserGroup whereUpdatedAt($value)
 * @property-read Collection<int, Account>            $accounts
 * @property-read int|null                            $accounts_count
 * @property-read Collection<int, AvailableBudget>    $availableBudgets
 * @property-read int|null                            $available_budgets_count
 * @property-read Collection<int, Bill>               $bills
 * @property-read int|null                            $bills_count
 * @property-read Collection<int, Budget>             $budgets
 * @property-read int|null                            $budgets_count
 * @property-read Collection<int, PiggyBank>          $piggyBanks
 * @property-read int|null                            $piggy_banks_count
 * @property-read Collection<int, TransactionJournal> $transactionJournals
 * @property-read int|null                            $transaction_journals_count
 * @mixin Eloquent
 */
class UserGroup extends Model
{
    protected $fillable = ['title'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return UserGroup
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): UserGroup
    {
        if (auth()->check()) {
            $userGroupId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var UserGroup $userGroup */
            $userGroup = UserGroup::find($userGroupId);
            if (null === $userGroup) {
                throw new NotFoundHttpException();
            }
            // need at least ready only to be aware of the user group's existence,
            // but owner/full role (in the group) or global owner role may overrule this.
            if ($user->hasRoleInGroup($userGroup, UserRoleEnum::READ_ONLY, true, true)) {
                return $userGroup;
            }
        }
        throw new NotFoundHttpException();
    }

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
     * Link to attachments.
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
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
     * Link to categories.
     *
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Link to exchange rates.
     *
     * @return HasMany
     */
    public function currencyExchangeRates(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class);
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
     * @return HasMany
     */
    public function objectGroups(): HasMany
    {
        return $this->hasMany(ObjectGroup::class);
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
     * @return HasMany
     */
    public function recurrences(): HasMany
    {
        return $this->hasMany(Recurrence::class);
    }

    /**
     * @return HasMany
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany(RuleGroup::class);
    }

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * @return HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return HasMany
     */
    public function transactionGroups(): HasMany
    {
        return $this->hasMany(TransactionGroup::class);
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

    /**
     * @return HasMany
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
