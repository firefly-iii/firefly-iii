<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionJournal
 *
 * @package FireflyIII\Models
 * @SuppressWarnings (PHPMD.TooManyMethods)
 * @property integer                                                                             $id
 * @property \Carbon\Carbon                                                                      $created_at
 * @property \Carbon\Carbon                                                                      $updated_at
 * @property \Carbon\Carbon                                                                      $deleted_at
 * @property integer                                                                             $user_id
 * @property integer                                                                             $transaction_type_id
 * @property integer                                                                             $bill_id
 * @property integer                                                                             $transaction_currency_id
 * @property string                                                                              $description
 * @property boolean                                                                             $completed
 * @property \Carbon\Carbon                                                                      $date
 * @property boolean                                                                             $encrypted
 * @property integer                                                                             $order
 * @property-read \FireflyIII\Models\Bill                                                        $bill
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[]           $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[]         $categories
 * @property-read mixed                                                                          $actual_amount
 * @property-read mixed                                                                          $amount
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[]              $tags
 * @property-read mixed                                                                          $asset_account
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[]      $transactions
 * @property-read mixed                                                                          $corrected_actual_amount
 * @property-read mixed                                                                          $destination_account
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBankEvent[]   $piggyBankEvents
 * @property-read \FireflyIII\Models\TransactionCurrency                                         $transactionCurrency
 * @property-read \FireflyIII\Models\TransactionType                                             $transactionType
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionGroup[] $transactiongroups
 * @property-read \FireflyIII\User                                                               $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereTransactionTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereBillId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereCompleted($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereEncrypted($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereOrder($value)
 * @method static \FireflyIII\Models\TransactionJournal accountIs($account)
 * @method static \FireflyIII\Models\TransactionJournal after($date)
 * @method static \FireflyIII\Models\TransactionJournal before($date)
 * @method static \FireflyIII\Models\TransactionJournal onDate($date)
 * @method static \FireflyIII\Models\TransactionJournal transactionTypes($types)
 * @method static \FireflyIII\Models\TransactionJournal withRelevantData()
 * @property-read mixed                                                                          $expense_account
 * @property string                                                                              account_encrypted
 * @property bool                                                                                joinedTransactions
 * @property bool                                                                                joinedTransactionTypes
 * @property mixed                                                                               account_id
 * @property mixed                                                                               name
 * @property mixed                                                                               symbol
 */
class TransactionJournal extends Model
{
    use SoftDeletes, ValidatingTrait;

    protected $fillable = ['user_id', 'transaction_type_id', 'bill_id', 'transaction_currency_id', 'description', 'completed', 'date', 'encrypted'];
    protected $hidden   = ['encrypted'];
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
     * @return string
     */
    public function getActualAmountAttribute()
    {
        $amount = '0';
        /** @var Transaction $t */
        foreach ($this->transactions as $t) {
            if ($t->amount > 0) {
                $amount = $t->amount;
            }
        }

        return $amount;
    }

    /**
     * @return float
     */
    public function getAmountAttribute()
    {
        $amount = '0';
        bcscale(2);
        /** @var Transaction $t */
        foreach ($this->transactions as $t) {
            if ($t->amount > 0) {
                $amount = $t->amount;
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
                $amount = bcsub($amount, $other->actual_amount);
            }

            return $amount;
        }

        // if this journal is part of an advancePayment AND the journal is a deposit,
        // then the journal amount is correcting a withdrawal, and the amount is zero:
        if ($advancePayment && $this->transactionType->type == 'Deposit') {
            return '0';
        }


        // is balancing act?
        $balancingAct = $this->tags()->where('tagMode', 'balancingAct')->first();

        if ($balancingAct) {
            // this is the expense:
            if ($this->transactionType->type == 'Withdrawal') {
                $transfer = $balancingAct->transactionJournals()->transactionTypes(['Transfer'])->first();
                if ($transfer) {
                    $amount = bcsub($amount, $transfer->actual_amount);

                    return $amount;
                }
            } // @codeCoverageIgnore
        } // @codeCoverageIgnore

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
        // if it's a deposit, it's the one thats positive
        // if it's a withdrawal, it's the one thats negative
        // otherwise, it's either (return first one):

        switch ($this->transactionType->type) {
            case 'Deposit':
                return $this->transactions()->where('amount', '>', 0)->first()->account;
            case 'Withdrawal':
                return $this->transactions()->where('amount', '<', 0)->first()->account;

        }

        return $this->transactions()->first()->account;

    }

    /**
     * @return string
     */
    public function getCorrectAmountAttribute()
    {

        switch ($this->transactionType->type) {
            case 'Deposit':
                return $this->transactions()->where('amount', '>', 0)->first()->amount;
            case 'Withdrawal':
                return $this->transactions()->where('amount', '<', 0)->first()->amount;
        }

        return '0';
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
     * @return string[]
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
     * @return Account
     */
    public function getExpenseAccountAttribute()
    {
        // if it's a deposit, it's the one thats negative
        // if it's a withdrawal, it's the one thats positive
        // otherwise, it's either (return first one):

        switch ($this->transactionType->type) {
            case 'Deposit':
                return $this->transactions()->where('amount', '<', 0)->first()->account;
            case 'Withdrawal':
                return $this->transactions()->where('amount', '>', 0)->first()->account;

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
     * @return EloquentBuilder
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
     * @return EloquentBuilder
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
     * @return EloquentBuilder
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
