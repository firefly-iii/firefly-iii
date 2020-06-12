<?php
/**
 * Account.php
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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
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
 * @property Collection                                                  piggyBanks
 * @property string                                                      $interest
 * @property string                                                      $interestPeriod
 * @property string                                                      accountTypeString
 * @property Carbon                                                      created_at
 * @property Carbon                                                      updated_at
 * @SuppressWarnings (PHPMD.CouplingBetweenObjects)
 * @property \Illuminate\Support\Carbon|null                             $deleted_at
 * @property int                                                         $user_id
 * @property-read string                                                 $edit_name
 * @property-read \Illuminate\Database\Eloquent\Collection|Note[]        $notes
 * @property-read \Illuminate\Database\Eloquent\Collection|Transaction[] $transactions
 * @method static EloquentBuilder|Account accountTypeIn($types)
 * @method static bool|null forceDelete()
 * @method static EloquentBuilder|Account newModelQuery()
 * @method static EloquentBuilder|Account newQuery()
 * @method static Builder|Account onlyTrashed()
 * @method static EloquentBuilder|Account query()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Account whereAccountTypeId($value)
 * @method static EloquentBuilder|Account whereActive($value)
 * @method static EloquentBuilder|Account whereCreatedAt($value)
 * @method static EloquentBuilder|Account whereDeletedAt($value)
 * @method static EloquentBuilder|Account whereEncrypted($value)
 * @method static EloquentBuilder|Account whereIban($value)
 * @method static EloquentBuilder|Account whereId($value)
 * @method static EloquentBuilder|Account whereName($value)
 * @method static EloquentBuilder|Account whereUpdatedAt($value)
 * @method static EloquentBuilder|Account whereUserId($value)
 * @method static EloquentBuilder|Account whereVirtualBalance($value)
 * @method static Builder|Account withTrashed()
 * @method static Builder|Account withoutTrashed()
 * @mixin Eloquent
 * @property-read int|null                                              $account_meta_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Attachment[] $attachments
 * @property-read int|null                                              $attachments_count
 * @property-read string                                                $account_number
 * @property-read \Illuminate\Database\Eloquent\Collection|Location[]   $locations
 * @property-read int|null                                              $locations_count
 * @property-read int|null                                              $notes_count
 * @property-read int|null                                              $piggy_banks_count
 * @property-read int|null                                              $transactions_count
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
     * @throws NotFoundHttpException
     * @return Account
     */
    public static function routeBinder(string $value): Account
    {
        if (auth()->check()) {
            $accountId = (int) $value;
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
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
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
     * @return MorphMany
     */
    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
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
        $value = (string)$value;
        if('' === $value) {
            $value = null;
        }
        $this->attributes['virtual_balance'] = $value;
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
