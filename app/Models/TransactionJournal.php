<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Crypt;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\TransactionJournal
 *
 * @property integer                            $id
 * @property Carbon                             $created_at
 * @property Carbon                             $updated_at
 * @property Carbon                             $deleted_at
 * @property integer                            $user_id
 * @property integer                            $transaction_type_id
 * @property integer                            $bill_id
 * @property integer                            $transaction_currency_id
 * @property string                             $description
 * @property boolean                            $completed
 * @property Carbon                             $date
 * @property boolean                            $encrypted
 * @property integer                            $order
 * @property integer                            $tag_count
 * @property-read Bill                          $bill
 * @property-read Collection|Budget[]           $budgets
 * @property-read Collection|Category[]         $categories
 * @property-read mixed                         $amount_positive
 * @property-read mixed                         $amount
 * @property-read Collection|Tag[]              $tags
 * @property-read Collection|Transaction[]      $transactions
 * @property-read mixed                         $destination_account
 * @property-read mixed                         $source_account
 * @property-read Collection|PiggyBankEvent[]   $piggyBankEvents
 * @property-read Collection|Attachment[]       $attachments
 * @property-read TransactionCurrency           $transactionCurrency
 * @property-read TransactionType               $transactionType
 * @property-read Collection|TransactionGroup[] $transactiongroups
 * @property-read User                          $user
 * @method static Builder|TransactionJournal accountIs($account)
 * @method static Builder|TransactionJournal after($date)
 * @method static Builder|TransactionJournal before($date)
 * @method static Builder|TransactionJournal onDate($date)
 * @method static Builder|TransactionJournal transactionTypes($types)
 * @method static Builder|TransactionJournal withRelevantData()
 */
class TransactionJournal extends Model
{
    use SoftDeletes, ValidatingTrait;


    protected $fillable
                      = ['user_id', 'transaction_type_id', 'bill_id', 'transaction_currency_id', 'description', 'completed', 'date', 'encrypted', 'tag_count'];
    protected $hidden = ['encrypted'];
    protected $rules
                      = [
            'user_id'                 => 'required|exists:users,id',
            'transaction_type_id'     => 'required|exists:transaction_types,id',
            'bill_id'                 => 'exists:bills,id',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'description'             => 'required|between:1,1024',
            'completed'               => 'required|boolean',
            'date'                    => 'required|date',
            'encrypted'               => 'required|boolean',
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
    public function getAmountPositiveAttribute()
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
        $cache = new CacheProperties();
        $cache->addProperty($this->id);
        $cache->addProperty('amount');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        bcscale(2);
        $transaction = $this->transactions->sortByDesc('amount')->first();
        $amount      = $transaction->amount;
        if ($this->isWithdrawal()) {
            $amount = $amount * -1;
        }
        $cache->store($amount);

        return $amount;

    }

    /**
     * @param Tag $tag
     * @param     $amount
     *
     * @return string
     */
    protected function amountByTagAdvancePayment(Tag $tag, $amount)
    {
        if ($this->isWithdrawal()) {
            $others = $tag->transactionJournals()->transactionTypes([TransactionType::DEPOSIT])->get();
            foreach ($others as $other) {
                $amount = bcsub($amount, $other->amount_positive);
            }

            return $amount;
        }
        if ($this->isDeposit()) {
            return '0';
        }

        return $amount;
    }

    /**
     * @param $tag
     * @param $amount
     *
     * @return string
     */
    protected function amountByTagBalancingAct($tag, $amount)
    {
        if ($this->isWithdrawal()) {
            $transfer = $tag->transactionJournals()->transactionTypes([TransactionType::TRANSFER])->first();
            if ($transfer) {
                $amount = bcsub($amount, $transfer->amount_positive);

                return $amount;
            }
        }

        return $amount;
    }

    /**
     * Assuming the journal has only one tag. Parameter amount is used as fallback.
     *
     * @param Tag    $tag
     * @param string $amount
     *
     * @return string
     */
    protected function amountByTag(Tag $tag, $amount)
    {
        if ($tag->tagMode == 'advancePayment') {
            return $this->amountByTagAdvancePayment($tag, $amount);
        }

        if ($tag->tagMode == 'balancingAct') {
            return $this->amountByTagBalancingAct($tag, $amount);

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
     * @param string $amount
     *
     * @return string
     */
    public function amountByTags($amount)
    {
        $firstBalancingAct = $this->tags()->where('tagMode', 'balancingAct')->first();
        if ($firstBalancingAct) {
            return $this->amountByTag($firstBalancingAct, $amount);
        }

        $firstAdvancePayment = $this->tags()->where('tagMode', 'advancePayment')->first();
        if ($firstAdvancePayment) {
            return $this->amountByTag($firstAdvancePayment, $amount);
        }

        return $amount;
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
     * Save the model to the database.
     *
     * @param  array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        $count           = $this->tags()->count();
        $this->tag_count = $count;

        return parent::save($options);
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
        $account = $this->transactions()->where('amount', '>', 0)->first()->account;

        return $account;
    }

    /**
     * @return Account
     */
    public function getSourceAccountAttribute()
    {
        $account = $this->transactions()->where('amount', '<', 0)->first()->account;

        return $account;
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
            }, 'transactionType', 'transactionCurrency', 'budgets', 'categories', 'transactions.account.accounttype', 'bill']
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachments()
    {
        return $this->morphMany('FireflyIII\Models\Attachment', 'attachable');
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

    /**
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transactionType->type;
    }

    /**
     * @return bool
     */
    public function isWithdrawal()
    {
        if (!is_null($this->type)) {
            return $this->type == TransactionType::WITHDRAWAL;
        }

        return $this->transactionType->isWithdrawal();
    }

    /**
     * @return bool
     */
    public function isDeposit()
    {
        if (!is_null($this->type)) {
            return $this->type == TransactionType::DEPOSIT;
        }

        return $this->transactionType->isDeposit();
    }

    /**
     * @return bool
     */
    public function isTransfer()
    {
        if (!is_null($this->type)) {
            return $this->type == TransactionType::TRANSFER;
        }

        return $this->transactionType->isTransfer();
    }

    /**
     * @return bool
     */
    public function isOpeningBalance()
    {
        if (!is_null($this->type)) {
            return $this->type == TransactionType::OPENING_BALANCE;
        }

        return $this->transactionType->isOpeningBalance();
    }

    /**
     * @param $value
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public static function routeBinder($value)
    {
        if (Auth::check()) {
            $validTypes = [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER];
            $object     = TransactionJournal::where('transaction_journals.id', $value)
                                            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                            ->whereIn('transaction_types.type', $validTypes)
                                            ->where('user_id', Auth::user()->id)->first(['transaction_journals.*']);
            if ($object) {
                return $object;
            }
        }

        throw new NotFoundHttpException;
    }
}
