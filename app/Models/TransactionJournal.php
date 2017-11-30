<?php
/**
 * TransactionJournal.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Models\TransactionJournalTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;
use Preferences;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionJournal.
 */
class TransactionJournal extends Model
{
    use SoftDeletes, ValidatingTrait, TransactionJournalTrait;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
            'deleted_at'    => 'datetime',
            'date'          => 'date',
            'interest_date' => 'date',
            'book_date'     => 'date',
            'process_date'  => 'date',
            'order'         => 'int',
            'tag_count'     => 'int',
            'encrypted'     => 'boolean',
            'completed'     => 'boolean',
        ];
    /** @var array */
    protected $dates = ['date', 'interest_date', 'book_date', 'process_date'];
    /** @var array */
    protected $fillable
        = ['user_id', 'transaction_type_id', 'bill_id', 'interest_date', 'book_date', 'process_date',
           'transaction_currency_id', 'description', 'completed',
           'date', 'rent_date', 'encrypted', 'tag_count',];
    /** @var array */
    protected $hidden = ['encrypted'];
    /** @var array */
    protected $rules
        = [
            'user_id'             => 'required|exists:users,id',
            'transaction_type_id' => 'required|exists:transaction_types,id',
            'description'         => 'required|between:1,1024',
            'completed'           => 'required|boolean',
            'date'                => 'required|date',
            'encrypted'           => 'required|boolean',
        ];

    /**
     * @param $value
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder($value)
    {
        if (auth()->check()) {
            $object = self::where('transaction_journals.id', $value)
                          ->with('transactionType')
                          ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                          ->where('user_id', auth()->user()->id)->first(['transaction_journals.*']);
            if (null !== $object) {
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill()
    {
        return $this->belongsTo('FireflyIII\Models\Bill');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany('FireflyIII\Models\Budget');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany('FireflyIII\Models\Category');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function deleteMeta(string $name): bool
    {
        $this->transactionJournalMeta()->where('name', $name)->delete();

        return true;
    }

    /**
     * @return HasMany
     */
    public function destinationJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'destination_id');
    }

    /**
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
     * @param string $name
     *
     * @return string
     */
    public function getMeta(string $name)
    {
        $value = null;
        $cache = new CacheProperties;
        $cache->addProperty('journal-meta');
        $cache->addProperty($this->id);
        $cache->addProperty($name);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        Log::debug(sprintf('Looking for journal #%d meta field "%s".', $this->id, $name));
        $entry = $this->transactionJournalMeta()->where('name', $name)->first();
        if (null !== $entry) {
            $value = $entry->data;
            // cache:
            $cache->store($value);
        }

        // convert to Carbon if name is _date
        if (null !== $value && '_date' === substr($name, -5)) {
            $value = new Carbon($value);
            // cache:
            $cache->store($value);
        }

        return $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasMeta(string $name): bool
    {
        return null !== $this->getMeta($name);
    }

    /**
     * @return bool
     */
    public function isDeposit(): bool
    {
        if (null !== $this->transaction_type_type) {
            return TransactionType::DEPOSIT === $this->transaction_type_type;
        }

        return $this->transactionType->isDeposit();
    }

    /**
     * @return bool
     */
    public function isOpeningBalance(): bool
    {
        if (null !== $this->transaction_type_type) {
            return TransactionType::OPENING_BALANCE === $this->transaction_type_type;
        }

        return $this->transactionType->isOpeningBalance();
    }

    /**
     * @return bool
     */
    public function isTransfer(): bool
    {
        if (null !== $this->transaction_type_type) {
            return TransactionType::TRANSFER === $this->transaction_type_type;
        }

        return $this->transactionType->isTransfer();
    }

    /**
     * @return bool
     */
    public function isWithdrawal(): bool
    {
        if (null !== $this->transaction_type_type) {
            return TransactionType::WITHDRAWAL === $this->transaction_type_type;
        }

        return $this->transactionType->isWithdrawal();
    }

    /**
     * Get all of the notes.
     */
    public function notes()
    {
        return $this->morphMany('FireflyIII\Models\Note', 'noteable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     * Save the model to the database.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = []): bool
    {
        $count           = $this->tags()->count();
        $this->tag_count = $count;

        return parent::save($options);
    }

    /**
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
    public function scopeSortCorrectly(EloquentBuilder $query)
    {
        $query->orderBy('transaction_journals.date', 'DESC');
        $query->orderBy('transaction_journals.order', 'ASC');
        $query->orderBy('transaction_journals.id', 'DESC');
    }

    /**
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeTransactionTypes(EloquentBuilder $query, array $types)
    {
        if (!self::isJoined($query, 'transaction_types')) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        }
        if (count($types) > 0) {
            $query->whereIn('transaction_types.type', $types);
        }
    }

    /**
     * @param $value
     */
    public function setDescriptionAttribute($value)
    {
        $encrypt                         = config('firefly.encryption');
        $this->attributes['description'] = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted']   = $encrypt;
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return TransactionJournalMeta
     */
    public function setMeta(string $name, $value): TransactionJournalMeta
    {
        if (null === $value) {
            $this->deleteMeta($name);

            return new TransactionJournalMeta();
        }
        if (is_string($value) && 0 === strlen($value)) {
            $this->deleteMeta($name);

            return new TransactionJournalMeta();
        }

        if ($value instanceof Carbon) {
            $value = $value->toW3cString();
        }

        Log::debug(sprintf('Going to set "%s" with value "%s"', $name, json_encode($value)));
        $entry = $this->transactionJournalMeta()->where('name', $name)->first();
        if (null === $entry) {
            $entry = new TransactionJournalMeta();
            $entry->transactionJournal()->associate($this);
            $entry->name = $name;
        }
        $entry->data = $value;
        $entry->save();
        Preferences::mark();

        return $entry;
    }

    /**
     * @return HasMany
     */
    public function sourceJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'source_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('FireflyIII\Models\Tag');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionCurrency()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionCurrency');
    }

    /**
     * @return HasMany
     */
    public function transactionJournalMeta(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournalMeta');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionType()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionType');
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
