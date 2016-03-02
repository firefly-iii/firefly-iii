<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\TransactionJournal
 *
 * @property integer                                                                                   $id
 * @property \Carbon\Carbon                                                                            $created_at
 * @property \Carbon\Carbon                                                                            $updated_at
 * @property \Carbon\Carbon                                                                            $deleted_at
 * @property integer                                                                                   $user_id
 * @property integer                                                                                   $transaction_type_id
 * @property integer                                                                                   $bill_id
 * @property integer                                                                                   $transaction_currency_id
 * @property string                                                                                    $description
 * @property boolean                                                                                   $completed
 * @property \Carbon\Carbon                                                                            $date
 * @property \Carbon\Carbon                                                                            $interest_date
 * @property \Carbon\Carbon                                                                            $book_date
 * @property boolean                                                                                   $encrypted
 * @property integer                                                                                   $order
 * @property integer                                                                                   $tag_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[]             $attachments
 * @property-read \FireflyIII\Models\Bill                                                              $bill
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[]                 $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[]               $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBankEvent[]         $piggyBankEvents
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[]                    $tags
 * @property-read \FireflyIII\Models\TransactionCurrency                                               $transactionCurrency
 * @property-read \FireflyIII\Models\TransactionType                                                   $transactionType
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionGroup[]       $transactiongroups
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournalMeta[] $transactionjournalmeta
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[]            $transactions
 * @property-read \FireflyIII\User                                                                     $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal after($date)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal before($date)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal transactionTypes($types)
 */
class TransactionJournal extends Model
{
    use SoftDeletes, ValidatingTrait;

    /**
     * Fields which queries must load.
     * ['transaction_journals.*', 'transaction_currencies.symbol', 'transaction_types.type']
     */
    const QUERYFIELDS
        = [
            'transaction_journals.*',
            'transaction_types.type as transaction_type_type', // the other field is called "transaction_type_id" so this is pretty consistent.
        ];
    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'date', 'deleted_at', 'interest_date', 'book_date'];
    /** @var array */
    protected $fillable
        = ['user_id', 'transaction_type_id', 'bill_id',
           'transaction_currency_id', 'description', 'completed',
           'date', 'rent_date', 'book_date', 'encrypted', 'tag_count'];
    /** @var array */
    protected $hidden = ['encrypted'];
    /** @var array */
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
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getMeta($fieldName): string
    {
        foreach ($this->transactionjournalmeta as $meta) {
            if ($meta->name == $fieldName) {
                return $meta->data;
            }
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isDeposit()
    {
        if (!is_null($this->transaction_type_type)) {
            return $this->transaction_type_type == TransactionType::DEPOSIT;
        }

        return $this->transactionType->isDeposit();
    }

    /**
     *
     * @return bool
     */
    public function isOpeningBalance()
    {
        if (!is_null($this->transaction_type_type)) {
            return $this->transaction_type_type == TransactionType::OPENING_BALANCE;
        }

        return $this->transactionType->isOpeningBalance();
    }

    /**
     *
     * @return bool
     */
    public function isTransfer()
    {
        if (!is_null($this->transaction_type_type)) {
            return $this->transaction_type_type == TransactionType::TRANSFER;
        }

        return $this->transactionType->isTransfer();
    }

    /**
     *
     * @return bool
     */
    public function isWithdrawal()
    {
        if (!is_null($this->transaction_type_type)) {
            return $this->transaction_type_type == TransactionType::WITHDRAWAL;
        }

        return $this->transactionType->isWithdrawal();
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
     * @param EloquentBuilder $query
     */
    public function scopeExpanded(EloquentBuilder $query)
    {
        // left join transaction type:
        $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');

        // try to get amount

    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeTransactionTypes(EloquentBuilder $query, array $types)
    {
        $query->leftJoin(
            'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
        );
        $query->whereIn('transaction_types.type', $types);
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
     * @return HasMany
     */
    public function transactionjournalmeta(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournalMeta');
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
