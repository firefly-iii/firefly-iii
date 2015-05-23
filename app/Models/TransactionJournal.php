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
    public function getActualAmountAttribute()
    {
        $amount = 0;
        /** @var Transaction $t */
        foreach ($this->transactions as $t) {
            if ($t->amount > 0) {
                $amount = floatval($t->amount);
            }
        }

        return $amount;
    }

    /**
     * @return float
     */
    public function getAmountAttribute()
    {
        $amount = 0;
        /** @var Transaction $t */
        foreach ($this->transactions as $t) {
            if ($t->amount > 0) {
                $amount = floatval($t->amount);
            }
        }

        /*
         * If the journal has tags, it gets complicated.
         */
        if ($this->tags->count() == 0) {
            return $amount;
        }
        // if journal is part of advancePayment AND journal is a withdrawal,
        // then journal is being repaid by other journals, so the actual amount will lower:
        /** @var Tag $advancePayment */
        $advancePayment = $this->tags()->where('tagMode', 'advancePayment')->first();
        if ($advancePayment && $this->transactionType->type == 'Withdrawal') {

            // loop other deposits, remove from our amount.
            $others = $advancePayment->transactionJournals()->transactionTypes(['Deposit'])->get();
            foreach ($others as $other) {
                $amount -= $other->actualAmount;
            }

            return $amount;
        }

        // if this journal is part of an advancePayment AND the journal is a deposit,
        // then the journal amount is correcting a withdrawal, and the amount is zero:
        if ($advancePayment && $this->transactionType->type == 'Deposit') {
            return 0;
        }


        // is balancing act?
        $balancingAct = $this->tags()->where('tagMode', 'balancingAct')->first();
        if ($balancingAct) {
            // this is the transfer

            // this is the expense:
            if ($this->transactionType->type == 'Withdrawal') {
                $transfer = $balancingAct->transactionJournals()->transactionTypes(['Transfer'])->first();
                if ($transfer) {
                    $amount -= $transfer->actualAmount;

                    return $amount;
                }
            }
        }

        return $amount;
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
     * @return float
     */
    public function getCorrectedActualAmountAttribute()
    {
        $amount = 0;
        $type   = $this->transactionType->type;
        /** @var Transaction $t */
        foreach ($this->transactions as $t) {
            if ($t->amount > 0 && $type != 'Withdrawal') {
                $amount = floatval($t->amount);
                break;
            }
            if ($t->amount < 0 && $type == 'Withdrawal') {
                $amount = floatval($t->amount);
                break;
            }
        }

        return $amount;
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
     * @return Account
     */
    public function getDestinationAccountAttribute()
    {
        /** @var Transaction $transaction */
        foreach ($this->transactions()->get() as $transaction) {
            if (floatval($transaction->amount) > 0) {
                return $transaction->account;
            }
        }

        return $this->transactions()->first()->account;
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
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeOnDate(EloquentBuilder $query, Carbon $date)
    {
        return $query->where('date', '=', $date->format('Y-m-d'));
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
