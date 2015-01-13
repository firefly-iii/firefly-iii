<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionJournal
 */
class TransactionJournal extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;

    protected $fillable
        = ['transaction_type_id', 'transaction_currency_id', 'user_id',
           'description', 'date', 'completed'];
    protected $rules
        = ['transaction_type_id'     => 'required|exists:transaction_types,id',
           'transaction_currency_id' => 'required|exists:transaction_currencies,id',
           'description'             => 'required|between:1,255',
           'date'                    => 'required|date',
           'completed'               => 'required|between:0,1'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill()
    {
        return $this->belongsTo('Bill');
    }

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
     * @param Account $account
     *
     * @return float
     */
    public function getAmount(\Account $account = null)
    {
        $amount = 0;
        foreach ($this->transactions as $t) {
            if (!is_null($account) && $account->id == $t->account_id) {
                $amount = floatval($t->amount);
                break;
            }
            if (floatval($t->amount) > 0) {
                $amount = floatval($t->amount);
                break;
            }
        }

        return $amount;
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
     * @param EloquentBuilder $query
     * @param Account         $account
     */
    public function scopeAccountIs(EloquentBuilder $query, \Account $account)
    {
        if (!isset($this->joinedTransactions)) {
            $query->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id');
            $this->joinedTransactions = true;
        }
        $query->where('transactions.account_id', $account->id);
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeAfter(EloquentBuilder $query, Carbon $date)
    {
        return $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeBefore(EloquentBuilder $query, Carbon $date)
    {
        return $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param EloquentBuilder $query
     * @param                 $amount
     */
    public function scopeLessThan(EloquentBuilder $query, $amount)
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
     * @param EloquentBuilder $query
     * @param                 $amount
     */
    public function scopeMoreThan(EloquentBuilder $query, $amount)
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
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeOnDate(EloquentBuilder $query, Carbon $date)
    {
        return $query->where('date', '=', $date->format('Y-m-d'));
    }

    /**
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeTransactionTypes(EloquentBuilder $query, array $types)
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
     * @param EloquentBuilder $query
     */
    public function scopeWithRelevantData(EloquentBuilder $query)
    {
        $query->with(
            ['transactions' => function (HasMany $q) {
                $q->orderBy('amount', 'ASC');
            }, 'transactiontype', 'budgets', 'categories', 'transactions.account.accounttype', 'bill', 'budgets', 'categories']
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
