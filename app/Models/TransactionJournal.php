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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Models\TransactionJournalTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;
use Preferences;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionJournal.
 *
 * @property User $user
 * @property int  $bill_id
 * @property Collection $categories
 */
class TransactionJournal extends Model
{
    use SoftDeletes, TransactionJournalTrait;

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
    protected $fillable
        = ['user_id', 'transaction_type_id', 'bill_id', 'interest_date', 'book_date', 'process_date',
           'transaction_currency_id', 'description', 'completed',
           'date', 'rent_date', 'encrypted', 'tag_count',];
    /** @var array */
    protected $hidden = ['encrypted'];

    /**
     * @param string $value
     *
     * @return TransactionJournal
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value): TransactionJournal
    {
        if (auth()->check()) {
            $journalId = (int)$value;
            $journal   = auth()->user()->transactionJournals()->where('transaction_journals.id', $journalId)
                               ->first(['transaction_journals.*']);
            if (null !== $journal) {
                return $journal;
            }
        }

        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(Budget::class);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @codeCoverageIgnore
     * @deprecated
     *
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
     * @codeCoverageIgnore
     *
     * @return HasMany
     */
    public function destinationJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'destination_id');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
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
     * @param string $name
     *
     * @deprecated
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
     * @codeCoverageIgnore
     *
     * @param string $name
     *
     * @deprecated
     * @return bool
     */
    public function hasMeta(string $name): bool
    {
        return null !== $this->getMeta($name);
    }

    /**
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents(): HasMany
    {
        return $this->hasMany(PiggyBankEvent::class);
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
     * @param array           $types
     */
    public function scopeTransactionTypes(EloquentBuilder $query, array $types)
    {
        if (!self::isJoined($query, 'transaction_types')) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        }
        if (\count($types) > 0) {
            $query->whereIn('transaction_types.type', $types);
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setDescriptionAttribute($value)
    {
        $encrypt                         = config('firefly.encryption');
        $this->attributes['description'] = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted']   = $encrypt;
    }

    /**
     * @deprecated
     *
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
        if (\is_string($value) && 0 === \strlen($value)) {
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
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function sourceJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'source_id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionCurrency()
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournalMeta(): HasMany
    {
        return $this->hasMany(TransactionJournalMeta::class);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
