<?php

/**
 * TransactionCurrency.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\TransactionCurrency
 *
 * @property int                             $id
 * @property null|Carbon                     $created_at
 * @property null|Carbon                     $updated_at
 * @property null|Carbon                     $deleted_at
 * @property bool                            $enabled
 * @property null|bool                       $userGroupDefault
 * @property null|bool                       $userGroupEnabled
 * @property string                          $code
 * @property string                          $name
 * @property string                          $symbol
 * @property int                             $decimal_places
 * @property BudgetLimit[]|Collection        $budgetLimits
 * @property null|int                        $budget_limits_count
 * @property Collection|TransactionJournal[] $transactionJournals
 * @property null|int                        $transaction_journals_count
 * @property Collection|Transaction[]        $transactions
 * @property null|int                        $transactions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency newQuery()
 * @method static Builder|TransactionCurrency                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency query()
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereDecimalPlaces($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransactionCurrency whereUpdatedAt($value)
 * @method static Builder|TransactionCurrency                               withTrashed()
 * @method static Builder|TransactionCurrency                               withoutTrashed()
 *
 * @property Collection<int, UserGroup> $userGroups
 * @property null|int                   $user_groups_count
 * @property Collection<int, User>      $users
 * @property null|int                   $users_count
 *
 * @mixin Eloquent
 */
class TransactionCurrency extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    public ?bool $userGroupDefault;
    public ?bool $userGroupEnabled;
    protected $casts
        = [
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
            'decimal_places' => 'int',
            'enabled'        => 'bool',
        ];

    protected $fillable = ['name', 'code', 'symbol', 'decimal_places', 'enabled'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $currencyId = (int)$value;
            $currency   = self::find($currencyId);
            if (null !== $currency) {
                $currency->refreshForUser(auth()->user());

                return $currency;
            }
        }

        throw new NotFoundHttpException();
    }

    public function refreshForUser(User $user): void
    {
        $current                = $user->userGroup->currencies()->where('transaction_currencies.id', $this->id)->first();
        $default                = app('amount')->getDefaultCurrencyByUserGroup($user->userGroup);
        $this->userGroupDefault = $default->id === $this->id;
        $this->userGroupEnabled = null !== $current;
    }

    public function budgetLimits(): HasMany
    {
        return $this->hasMany(BudgetLimit::class);
    }

    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Link to user groups
     */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class)->withTimestamps()->withPivot('group_default');
    }

    /**
     * Link to users
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('user_default');
    }

    protected function decimalPlaces(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
