<?php
/**
 * Account.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Account.
 *
 * @property int                                                                            $id
 * @property string                                                                         $name
 * @property string                                                                         $iban
 * @property AccountType                                                                    $accountType
 * @property bool                                                                           $active
 * @property string                                                                         $virtual_balance
 * @property User                                                                           $user
 * @property string                                                                         startBalance
 * @property string                                                                         endBalance
 * @property string                                                                         difference
 * @property Carbon                                                                         lastActivityDate
 * @property Collection                                                                     accountMeta
 * @property bool                                                                           encrypted
 * @property int                                                                            account_type_id
 * @property Collection                                                                     piggyBanks
 * @property string                                                                         $interest
 * @property string                                                                         $interestPeriod
 * @property string                                                                         accountTypeString
 * @property Carbon                                                                         created_at
 * @property Carbon                                                                         updated_at
 * @SuppressWarnings (PHPMD.CouplingBetweenObjects)
 * @property \Illuminate\Support\Carbon|null                                                $deleted_at
 * @property int                                                                            $user_id
 * @property-read string                                                                    $edit_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Note[]        $notes
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[] $transactions
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account accountTypeIn($types)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereAccountTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Account whereVirtualBalance($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Account withoutTrashed()
 * @mixin \Eloquent
 */
class Account extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'user_id'    => 'integer',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'account_type_id', 'name', 'active', 'virtual_balance', 'iban'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];
    /** @var bool */
    private $joinedAccountTypes;

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Account
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Account
    {
        if (auth()->check()) {
            $accountId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Account $account */
            $account = $user->accounts()->find($accountId);
            if (null !== $account) {
                return $account;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function accountMeta(): HasMany
    {
        return $this->hasMany(AccountMeta::class);
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * Get the account number.
     *
     * @return string
     */
    public function getAccountNumberAttribute(): string
    {
        /** @var AccountMeta $metaValue */
        $metaValue = $this->accountMeta()
                          ->where('name', 'account_number')
                          ->first();

        return $metaValue ? $metaValue->data : '';
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getEditNameAttribute(): string
    {
        $name = $this->name;

        if (AccountType::CASH === $this->accountType->type) {
            return '';
        }

        return $name;
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function piggyBanks(): HasMany
    {
        return $this->hasMany(PiggyBank::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeAccountTypeIn(EloquentBuilder $query, array $types): void
    {
        if (null === $this->joinedAccountTypes) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setVirtualBalanceAttribute($value): void
    {
        $this->attributes['virtual_balance'] = (string)$value;
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
