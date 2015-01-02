<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TransactionJournal
 */
class TransactionJournal extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;

    protected $rules
        = ['transaction_type_id'     => 'required|exists:transaction_types,id',
           'transaction_currency_id' => 'required|exists:transaction_currencies,id',
           'description'             => 'required|between:1,255',
           'date'                    => 'required|date',
           'completed'               => 'required|between:0,1'];
    protected     $fillable
        = ['transaction_type_id', 'transaction_currency_id', 'user_id',
           'description', 'date', 'completed'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets()
    {
        return $this->belongsToMany(
            'Budget'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(
            'Category'
        );
    }


    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @param Account $account
     *
     * @return float
     */
    public function getAmount(\Account $account = null)
    {

        foreach ($this->transactions as $t) {
            if (!is_null($account) && $account->id == $t->account_id) {
                return floatval($t->amount);
            }
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
    public function piggyBankEvents()
    {
        return $this->hasMany('PiggyBankEvent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill()
    {
        return $this->belongsTo('Bill');
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
        return $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param                $query
     * @param Carbon         $date
     *
     * @return mixed
     */
    public function scopeBefore($query, Carbon $date)
    {
        return $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param Builder $query
     * @param         $amount
     */
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

    /**
     * @param Builder $query
     * @param         $amount
     */
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

    /**
     * @param Builder $query
     * @param array   $types
     */
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
            ['transactions'                    => function (Builder $q) {
                $q->orderBy('amount', 'ASC');
            }, 'transactiontype', 'budgets','categories', 'transactions.account.accounttype', 'bill', 'budgets', 'categories']
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactiongroups()
    {
        return $this->belongsToMany('TransactionGroup');
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