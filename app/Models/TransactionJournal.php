<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Support\CacheProperties;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionJournal
 *
 * @package FireflyIII\Models
 * @property integer $id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property \Carbon\Carbon $deleted_at 
 * @property integer $user_id 
 * @property integer $transaction_type_id 
 * @property integer $bill_id 
 * @property integer $transaction_currency_id 
 * @property string $description 
 * @property boolean $completed 
 * @property \Carbon\Carbon $date 
 * @property boolean $encrypted 
 * @property integer $order 
 * @property integer $tag_count 
 * @property-read \FireflyIII\Models\Bill $bill 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[] $budgets 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[] $categories 
 * @property-read mixed $actual_amount 
 * @property-read mixed $amount 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[] $tags 
 * @property-read mixed $correct_amount 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[] $transactions 
 * @property-read mixed $destination_account 
 * @property-read mixed $source_account 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBankEvent[] $piggyBankEvents 
 * @property-read \FireflyIII\Models\TransactionCurrency $transactionCurrency 
 * @property-read \FireflyIII\Models\TransactionType $transactionType 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionGroup[] $transactiongroups 
 * @property-read \FireflyIII\User $user
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
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal whereTagCount($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal accountIs($account)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal after($date)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal before($date)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal onDate($date)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal transactionTypes($types)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal withRelevantData()
 * @property-read bool $account_encrypted
 * @property-read bool $joinedTransactions
 * @property-read bool $joinedTransactionTypes
 * @property-read int $account_id
 * @property-read string $name
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
        $cache = new CacheProperties();
        $cache->addProperty($this->id);
        $cache->addProperty('amount');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        bcscale(2);
        $set    = $this->transactions->sortByDesc('amount');
        $amount = $set->first()->amount;

        if (intval($this->tag_count) === 1) {
            // get amount for single tag:
            $amount = $this->amountByTag($this->tags()->first(), $amount);
        }

        if (intval($this->tag_count) > 1) {
            // get amount for either tag.
            $amount = $this->amountByTags($amount);

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
        if ($this->transactionType->type == 'Withdrawal') {
            $others = $tag->transactionJournals()->transactionTypes(['Deposit'])->get();
            foreach ($others as $other) {
                $amount = bcsub($amount, $other->actual_amount);
            }

            return $amount;
        }
        if ($this->transactionType->type == 'Deposit') {
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
        if ($this->transactionType->type == 'Withdrawal') {
            $transfer = $tag->transactionJournals()->transactionTypes(['Transfer'])->first();
            if ($transfer) {
                $amount = bcsub($amount, $transfer->actual_amount);

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

        return $this->transactions()->where('amount', '>', 0)->first()->amount;
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
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('destinationAccount');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $account = $this->transactions()->where('amount', '>', 0)->first()->account;
        $cache->store($account);

        return $account;
    }

    /**
     * @return Account
     */
    public function getSourceAccountAttribute()
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('sourceAccount');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $account = $this->transactions()->where('amount', '<', 0)->first()->account;

        $cache->store($account);

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
