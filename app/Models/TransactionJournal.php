<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionJournal
 *
 * @package FireflyIII\Models
 */
class TransactionJournal extends Model
{
    use SoftDeletes, ValidatingTrait;

    protected $fillable = ['user_id', 'transaction_type_id', 'bill_id', 'transaction_currency_id', 'description', 'completed', 'date', 'encrypted'];

    protected $rules
        = [
            'user_id'                 => 'required|exists:users,id',
            'transaction_type_id'     => 'required|exists:transaction_types,id',
            'bill_id'                 => 'exists:bills,id',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'description'             => 'required|between:1,1024',
            'completed'               => 'required|boolean',
            'date'                    => 'required|date',
            'encrypted'               => 'required|boolean'
        ];

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill()
    {
        return $this->belongsTo('FireflyIII\Models\Bill');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets()
    {
        return $this->belongsToMany('FireflyIII\Models\Budget');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany('FireflyIII\Models\Category');
    }

    /**
     * @return float
     */
    public function getAmountAttribute()
    {
        /** @var Transaction $t */
        foreach ($this->transactions as $t) {
            if ($t->amount > 0) {
                return floatval($t->amount);
            }
        }

        return 0;
    }

    /**
     * @return Account
     */
    public function getAssetAccountAttribute()
    {
        $positive = true; // the asset account is in the transaction with the positive amount.
        if ($this->transactionType->type === 'Withdrawal') {
            $positive = false;
        }
        /** @var Transaction $transaction */
        foreach ($this->transactions()->get() as $transaction) {
            if (floatval($transaction->amount) > 0 && $positive === true) {
                return $transaction->account;
            }
            if (floatval($transaction->amount) < 0 && $positive === false) {
                return $transaction->account;
            }

        }

        return $this->transactions()->first()->account;
    }


    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date', 'deleted_at'];
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param Account         $account
     */
    public function scopeAccountIs(EloquentBuilder $query, Account $account)
    {
        if (!isset($this->joinedTransactions)) {
            $query->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id');
            $this->joinedTransactions = true;
        }
        $query->where('transactions.account_id', $account->id);
    }

    /**
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     *
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
     * Returns the account to which the money was moved.
     *
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param Account         $account
     */
    public function scopeToAccountIs(EloquentBuilder $query, Account $account)
    {
        $query->leftJoin(
            'transactions', function (JoinClause $join) {
            $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '>', 0);
        }
        );
        $query->where('transactions.account_id', $account->id);
    }

    /**
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
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
            }, 'transactiontype', 'transactioncurrency', 'budgets', 'categories', 'transactions.account.accounttype', 'bill', 'budgets', 'categories']
        );
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
        $this->attributes['encrypted']   = true;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('FireflyIII\Models\Tag');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionCurrency()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionCurrency');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionType()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionType');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactiongroups()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionGroup');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
