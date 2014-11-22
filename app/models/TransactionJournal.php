<?php

use Carbon\Carbon;
use LaravelBook\Ardent\Ardent;
use LaravelBook\Ardent\Builder;


/**
 * TransactionJournal
 *
 * @property integer                                                         $id
 * @property \Carbon\Carbon                                                  $created_at
 * @property \Carbon\Carbon                                                  $updated_at
 * @property integer                                                         $user_id
 * @property integer                                                         $transaction_type_id
 * @property integer                                                         $recurring_transaction_id
 * @property integer                                                         $transaction_currency_id
 * @property string                                                          $description
 * @property boolean                                                         $completed
 * @property \Carbon\Carbon                                                  $date
 * @property-read \Illuminate\Database\Eloquent\Collection|\
 *             'Budget[] $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\
 *             'Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\Component[]      $components
 * @property-read \Illuminate\Database\Eloquent\Collection|\PiggybankEvent[] $piggybankevents
 * @property-read \RecurringTransaction                                      $recurringTransaction
 * @property-read \TransactionCurrency                                       $transactionCurrency
 * @property-read \TransactionType                                           $transactionType
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[]    $transactions
 * @property-read \User                                                      $user
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereTransactionTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereRecurringTransactionId($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereCompleted($value)
 * @method static \Illuminate\Database\Query\Builder|\TransactionJournal whereDate($value)
 * @method static \TransactionJournal accountIs($account)
 * @method static \TransactionJournal after($date)
 * @method static \TransactionJournal before($date)
 * @method static \TransactionJournal defaultSorting()
 * @method static \TransactionJournal lessThan($amount)
 * @method static \TransactionJournal moreThan($amount)
 * @method static \TransactionJournal onDate($date)
 * @method static \TransactionJournal transactionTypes($types)
 * @method static \TransactionJournal withRelevantData()
 * @property-read \Illuminate\Database\Eloquent\Collection|\
 *             'Budget[] $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\
 *             'Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\
 *             'Budget[] $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\
 *             'Category[] $categories
 */
class TransactionJournal extends Ardent
{

    public static $rules
        = ['transaction_type_id'     => 'required|exists:transaction_types,id',
           'transaction_currency_id' => 'required|exists:transaction_currencies,id',
           'description'             => 'required|between:1,255',
           'date'                    => 'required|date',
           'completed'               => 'required|between:0,1'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets()
    {
        return $this->belongsToMany(
            'Budget', 'component_transaction_journal', 'transaction_journal_id', 'component_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(
            'Category', 'component_transaction_journal', 'transaction_journal_id', 'component_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function components()
    {
        return $this->belongsToMany('Component');
    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @return float
     */
    public function getAmount()
    {

        foreach ($this->transactions as $t) {
            if (floatval($t->amount) > 0) {
                return floatval($t->amount);
            }
        }

        return -0.01;
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggybankevents()
    {
        return $this->hasMany('PiggybankEvent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recurringTransaction()
    {
        return $this->belongsTo('RecurringTransaction');
    }

    /**
     * @param Builder $query
     * @param Account $account
     */
    public function scopeAccountIs(Builder $query, \Account $account)
    {
        if (!isset($this->joinedTransactions)) {
            $query->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id');
            $this->joinedTransactions = true;
        }
        $query->where('transactions.account_id', $account->id);
    }

    /**
     * @param                $query
     * @param Carbon         $date
     *
     * @return mixed
     */
    public function scopeAfter($query, Carbon $date)
    {
        return $query->where('date', '>=', $date->format('Y-m-d'));
    }

    /**
     * @param                $query
     * @param Carbon         $date
     *
     * @return mixed
     */
    public function scopeBefore($query, Carbon $date)
    {
        return $query->where('date', '<=', $date->format('Y-m-d'));
    }

    public function scopeDefaultSorting(Builder $query)
    {
        $query->orderBy('date', 'DESC')->orderBy('transaction_journals.id', 'DESC');
    }

    public function scopeLessThan(Builder $query, $amount)
    {
        if (is_null($this->joinedTransactions)) {
            $query->leftJoin(
                'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
            );
            $this->joinedTransactions = true;
        }

        $query->where('transactions.amount', '<=', $amount);
    }

    public function scopeMoreThan(Builder $query, $amount)
    {
        if (is_null($this->joinedTransactions)) {
            $query->leftJoin(
                'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
            );
            $this->joinedTransactions = true;
        }

        $query->where('transactions.amount', '>=', $amount);
    }

    /**
     * @param        $query
     * @param Carbon $date
     *
     * @return mixed
     */
    public function scopeOnDate($query, Carbon $date)
    {
        return $query->where('date', '=', $date->format('Y-m-d'));
    }

    public function scopeTransactionTypes(Builder $query, array $types)
    {
        if (is_null($this->joinedTransactionTypes)) {
            $query->leftJoin(
                'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
            );
            $this->joinedTransactionTypes = true;
        }
        $query->whereIn('transaction_types.type', $types);
    }

    /**
     * Automatically includes the 'with' parameters to get relevant related
     * objects.
     *
     * @param $query
     */
    public function scopeWithRelevantData(Builder $query)
    {
        $query->with(
            ['transactions'                    => function ($q) {
                $q->orderBy('amount', 'ASC');
            }, 'transactiontype', 'components' => function ($q) {
                $q->orderBy('class');
            }, 'transactions.account.accounttype', 'recurringTransaction', 'budgets', 'categories']
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionCurrency()
    {
        return $this->belongsTo('TransactionCurrency');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionType()
    {
        return $this->belongsTo('TransactionType');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('Transaction');
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