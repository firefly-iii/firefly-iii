<?php
/**
 * Account.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * Class Account
 *
 * @package FireflyIII\Models
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
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    /** @var array */
    protected $fillable = ['user_id', 'account_type_id', 'name', 'active', 'virtual_balance', 'iban'];
    /** @var array */
    protected $hidden = ['encrypted'];
    protected $rules
                      = [
            'user_id'         => 'required|exists:users,id',
            'account_type_id' => 'required|exists:account_types,id',
            'name'            => 'required|between:1,200',
            'active'          => 'required|boolean',
            'iban'            => 'between:1,50|iban',
        ];
    /** @var  bool */
    private $joinedAccountTypes;

    /**
     * @param array $fields
     *
     * @return Account
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
     * @param Account $value
     *
     * @return Account
     */
    public static function routeBinder(Account $value)
    {

        if (auth()->check()) {
            if ($value->user_id === auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return HasMany
     */
    public function accountMeta(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\AccountMeta');
    }

    /**
     * @return BelongsTo
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\Models\AccountType');
    }

    /**
     * @return string
     */
    public function getEditNameAttribute(): string
    {
        $name = $this->name;

        if ($this->accountType->type === AccountType::CASH) {
            return '';
        }

        return $name;
    }

    /**
     * FIxxME can return null
     *
     * @param $value
     *
     * @return string
     * @throws FireflyException
     */
    public function getIbanAttribute($value): string
    {
        if (is_null($value) || strlen(strval($value)) === 0) {
            return '';
        }
        try {
            $result = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            throw new FireflyException('Cannot decrypt value "' . $value . '" for account #' . $this->id);
        }
        if (is_null($result)) {
            return '';
        }

        return $result;
    }

    /**
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
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value): string
    {

        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * Returns the opening balance
     *
     * @return TransactionJournal
     * @throws FireflyException
     */
    public function getOpeningBalance(): TransactionJournal
    {
        $journal = TransactionJournal::sortCorrectly()
                                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $this->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * Returns the amount of the opening balance for this account.
     *
     * @return string
     * @throws FireflyException
     */
    public function getOpeningBalanceAmount(): string
    {
        $journal = TransactionJournal::sortCorrectly()
                                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $this->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return '0';
        }

        $count = $journal->transactions()->count();
        if ($count !== 2) {
            throw new FireflyException(sprintf('Cannot use getFirstTransaction on journal #%d', $journal->id));
        }
        $transaction = $journal->transactions()->where('account_id', $this->id)->first();
        if (is_null($transaction)) {
            return '0';
        }

        return strval($transaction->amount);
    }

    /**
     * Returns the date of the opening balance for this account. If no date, will return 01-01-1900
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function getOpeningBalanceDate(): Carbon
    {
        $date    = new Carbon('1900-01-01');
        $journal = TransactionJournal::sortCorrectly()
                                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $this->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return $date;
        }

        return $journal->date;
    }

    /**
     * @return HasMany
     */
    public function piggyBanks(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\PiggyBank');
    }

    /**
     *
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeAccountTypeIn(EloquentBuilder $query, array $types)
    {
        if (is_null($this->joinedAccountTypes)) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    /**
     *
     * @param EloquentBuilder $query
     * @param string          $name
     * @param string          $value
     */
    public function scopeHasMetaValue(EloquentBuilder $query, $name, $value)
    {
        $joinName = str_replace('.', '_', $name);
        $query->leftJoin(
            'account_meta as ' . $joinName, function (JoinClause $join) use ($joinName, $name) {
            $join->on($joinName . '.account_id', '=', 'accounts.id')->where($joinName . '.name', '=', $name);
        }
        );
        $query->where($joinName . '.data', json_encode($value));
    }

    /**
     *
     * @param $value
     */
    public function setIbanAttribute($value)
    {
        $this->attributes['iban'] = Crypt::encrypt($value);
    }

    /**
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
     * @param $value
     *
     */
    public function setVirtualBalanceAttribute($value)
    {
        $this->attributes['virtual_balance'] = strval(round($value, 12));
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
