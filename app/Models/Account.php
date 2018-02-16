<?php
/**
 * Account.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Account.
 */
class Account extends Model
{
    use SoftDeletes, ValidatingTrait;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array */
    protected $fillable = ['user_id', 'account_type_id', 'name', 'active', 'virtual_balance', 'iban'];
    /** @var array */
    protected $hidden = ['encrypted'];
    /**
     * @var array
     */
    protected $rules
        = [
            'user_id'         => 'required|exists:users,id',
            'account_type_id' => 'required|exists:account_types,id',
            'name'            => 'required|between:1,200',
            'active'          => 'required|boolean',
            'iban'            => 'between:1,50|iban',
        ];
    /** @var bool */
    private $joinedAccountTypes;

    /**
     * @param array $fields
     *
     * @return Account
     *
     * @throws FireflyException
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        if (!isset($fields['user_id'])) {
            throw new FireflyException('Missing required field "user_id".');
        }
        // everything but the name:
        $query  = self::orderBy('id');
        $search = $fields;
        unset($search['name'], $search['iban']);

        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['accounts.*']);

        // account must have a name. If not set, use IBAN.
        if (!isset($fields['name'])) {
            $fields['name'] = $fields['iban'];
        }

        /** @var Account $account */
        foreach ($set as $account) {
            if ($account->name === $fields['name']) {
                return $account;
            }
        }

        // create it!
        $account = self::create($fields);

        return $account;
    }

    /**
     * @param string $value
     *
     * @return Account
     */
    public static function routeBinder(string $value): Account
    {
        if (auth()->check()) {
            $accountId = intval($value);
            $account   = auth()->user()->accounts()->find($accountId);
            if (!is_null($account)) {
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
        return $this->hasMany('FireflyIII\Models\AccountMeta');
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\Models\AccountType');
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
     * @param $value
     *
     * @return string
     *
     * @throws FireflyException
     */
    public function getIbanAttribute($value): string
    {
        if (null === $value || 0 === strlen(strval($value))) {
            return '';
        }
        try {
            $result = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            throw new FireflyException('Cannot decrypt value "' . $value . '" for account #' . $this->id);
        }
        if (null === $result) {
            return '';
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getMeta(string $fieldName): string
    {
        foreach ($this->accountMeta as $meta) {
            if ($meta->name === $fieldName) {
                return strval($meta->data);
            }
        }

        return '';
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value): ?string
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * Returns the opening balance.
     *
     * @return TransactionJournal
     */
    public function getOpeningBalance(): TransactionJournal
    {
        $journal = TransactionJournal::sortCorrectly()
                                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $this->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (null === $journal) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function piggyBanks(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\PiggyBank');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeAccountTypeIn(EloquentBuilder $query, array $types)
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
     * @param EloquentBuilder $query
     * @param string          $name
     * @param string          $value
     */
    public function scopeHasMetaValue(EloquentBuilder $query, $name, $value)
    {
        $joinName = str_replace('.', '_', $name);
        $query->leftJoin(
            'account_meta as ' . $joinName,
            function (JoinClause $join) use ($joinName, $name) {
                $join->on($joinName . '.account_id', '=', 'accounts.id')->where($joinName . '.name', '=', $name);
            }
        );
        $query->where($joinName . '.data', json_encode($value));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setIbanAttribute($value)
    {
        $this->attributes['iban'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $encrypt                       = config('firefly.encryption');
        $this->attributes['name']      = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = $encrypt;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setVirtualBalanceAttribute($value)
    {
        $this->attributes['virtual_balance'] = strval($value);
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function transactions(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
