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

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserGroup
 *
 * @property int                          $id
 * @property null|Carbon                  $created_at
 * @property null|Carbon                  $updated_at
 * @property null|string                  $deleted_at
 * @property string                       $title
 * @property Collection|GroupMembership[] $groupMemberships
 * @property null|int                     $group_memberships_count
 *
 * @method static Builder|UserGroup newModelQuery()
 * @method static Builder|UserGroup newQuery()
 * @method static Builder|UserGroup query()
 * @method static Builder|UserGroup whereCreatedAt($value)
 * @method static Builder|UserGroup whereDeletedAt($value)
 * @method static Builder|UserGroup whereId($value)
 * @method static Builder|UserGroup whereTitle($value)
 * @method static Builder|UserGroup whereUpdatedAt($value)
 *
 * @property Collection<int, Account>              $accounts
 * @property null|int                              $accounts_count
 * @property Collection<int, AvailableBudget>      $availableBudgets
 * @property null|int                              $available_budgets_count
 * @property Collection<int, Bill>                 $bills
 * @property null|int                              $bills_count
 * @property Collection<int, Budget>               $budgets
 * @property null|int                              $budgets_count
 * @property Collection<int, PiggyBank>            $piggyBanks
 * @property null|int                              $piggy_banks_count
 * @property Collection<int, TransactionJournal>   $transactionJournals
 * @property null|int                              $transaction_journals_count
 * @property Collection<int, Attachment>           $attachments
 * @property null|int                              $attachments_count
 * @property Collection<int, Category>             $categories
 * @property null|int                              $categories_count
 * @property Collection<int, CurrencyExchangeRate> $currencyExchangeRates
 * @property null|int                              $currency_exchange_rates_count
 * @property Collection<int, ObjectGroup>          $objectGroups
 * @property null|int                              $object_groups_count
 * @property Collection<int, Recurrence>           $recurrences
 * @property null|int                              $recurrences_count
 * @property Collection<int, RuleGroup>            $ruleGroups
 * @property null|int                              $rule_groups_count
 * @property Collection<int, Rule>                 $rules
 * @property null|int                              $rules_count
 * @property Collection<int, Tag>                  $tags
 * @property null|int                              $tags_count
 * @property Collection<int, TransactionGroup>     $transactionGroups
 * @property null|int                              $transaction_groups_count
 * @property Collection<int, Webhook>              $webhooks
 * @property null|int                              $webhooks_count
 * @property Collection<int, TransactionCurrency>  $currencies
 * @property null|int                              $currencies_count
 *
 * @mixin Eloquent
 */
class UserGroup extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = ['title'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $userGroupId = (int)$value;

            /** @var User $user */
            $user = auth()->user();

            /** @var null|UserGroup $userGroup */
            $userGroup = self::find($userGroupId);
            if (null === $userGroup) {
                throw new NotFoundHttpException();
            }
            // need at least ready only to be aware of the user group's existence,
            // but owner/full role (in the group) or global owner role may overrule this.
            $access = $user->hasRoleInGroupOrOwner($userGroup, UserRoleEnum::READ_ONLY) || $user->hasRole('owner');
            if ($access) {
                return $userGroup;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * Link to accounts.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Link to attachments.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Link to bills.
     */
    public function availableBudgets(): HasMany
    {
        return $this->hasMany(AvailableBudget::class);
    }

    /**
     * Link to bills.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Link to budgets.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Link to categories.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Link to currencies
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(TransactionCurrency::class)->withTimestamps()->withPivot('group_default');
    }

    /**
     * Link to exchange rates.
     */
    public function currencyExchangeRates(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class);
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    public function objectGroups(): HasMany
    {
        return $this->hasMany(ObjectGroup::class);
    }

    /**
     * Link to piggy banks.
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough(PiggyBank::class, Account::class);
    }

    public function recurrences(): HasMany
    {
        return $this->hasMany(Recurrence::class);
    }

    public function ruleGroups(): HasMany
    {
        return $this->hasMany(RuleGroup::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function transactionGroups(): HasMany
    {
        return $this->hasMany(TransactionGroup::class);
    }

    /**
     * Link to transaction journals.
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
