<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;
use LaravelBook\Ardent\Builder;

/**
 * Account
 *
 * @property integer                                                      $id
 * @property \Carbon\Carbon                                               $created_at
 * @property \Carbon\Carbon                                               $updated_at
 * @property integer                                                      $user_id
 * @property integer                                                      $account_type_id
 * @property string                                                       $name
 * @property boolean                                                      $active
 * @property-read \AccountType                                            $accountType
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[] $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\Piggybank[]   $piggybanks
 * @property-read \User                                                   $user
 * @method static \Illuminate\Database\Query\Builder|\Account whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereAccountTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Account whereActive($value)
 * @method static \Account accountTypeIn($types)
 */
class Account extends Ardent
{

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules
        = ['name'            => ['required', 'between:1,100', 'alphabasic'], 'user_id' => 'required|exists:users,id',
           'account_type_id' => 'required|exists:account_types,id', 'active' => 'required|boolean'

        ];

    protected $fillable = ['name', 'user_id', 'account_type_id', 'active'];

    /**
     * Account type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountType()
    {
        return $this->belongsTo('AccountType');
    }

    /**
     * Get an accounts current balance.
     *
     * @param \Carbon\Carbon $date
     *
     * @return float
     */
    public function balance(\Carbon\Carbon $date = null)
    {
        $date = is_null($date) ? new \Carbon\Carbon : $date;

        return floatval(
            $this->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return float
     */
    public function balanceBeforeJournal(TransactionJournal $journal)
    {
        return floatval(
            $this->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $journal->date->format('Y-m-d'))->where(
                'transaction_journals.created_at', '<=', $journal->created_at->format('Y-m-d H:i:s')
            )->where('transaction_journals.id', '!=', $journal->id)->sum('transactions.amount')
        );
    }

    /**
     * @return Carbon
     */
    public function lastActionDate()
    {
        $transaction = $this->transactions()->orderBy('updated_at', 'DESC')->first();
        if(is_null($transaction)) {
            return null;
        }

        return $transaction->updated_at;
    }

    /**
     * Transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggybanks()
    {
        return $this->hasMany('Piggybank');
    }

    /**
     * @param \Carbon\Carbon $date
     *
     * @return null
     */
    public function predict(
        /** @noinspection PhpUnusedParameterInspection */
        \Carbon\Carbon $date
    ) {
        return null;
    }

    /**
     * @param Builder $query
     * @param array   $types
     */
    public function scopeAccountTypeIn(Builder $query, array $types)
    {
        if (is_null($this->joinedAccountTypes)) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }


}