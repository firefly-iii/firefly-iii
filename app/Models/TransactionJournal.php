<?php
/**
 * TransactionJournal.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * FireflyIII\Models\TransactionJournal
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $user_id
 * @property int $transaction_type_id
 * @property int|null $transaction_group_id
 * @property int|null $bill_id
 * @property int|null $transaction_currency_id
 * @property string $description
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $interest_date
 * @property \Illuminate\Support\Carbon|null $book_date
 * @property \Illuminate\Support\Carbon|null $process_date
 * @property int $order
 * @property int $tag_count
 * @property bool $encrypted
 * @property bool $completed
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[] $attachments
 * @property-read int|null $attachments_count
 * @property-read \FireflyIII\Models\Bill|null $bill
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[] $budgets
 * @property-read int|null $budgets_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[] $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournalLink[] $destJournalLinks
 * @property-read int|null $dest_journal_links_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Note[] $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBankEvent[] $piggyBankEvents
 * @property-read int|null $piggy_bank_events_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournalLink[] $sourceJournalLinks
 * @property-read int|null $source_journal_links_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \FireflyIII\Models\TransactionCurrency|null $transactionCurrency
 * @property-read \FireflyIII\Models\TransactionGroup|null $transactionGroup
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournalMeta[] $transactionJournalMeta
 * @property-read int|null $transaction_journal_meta_count
 * @property-read \FireflyIII\Models\TransactionType $transactionType
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[] $transactions
 * @property-read int|null $transactions_count
 * @property-read User $user
 * @method static EloquentBuilder|TransactionJournal after(\Carbon\Carbon $date)
 * @method static EloquentBuilder|TransactionJournal before(\Carbon\Carbon $date)
 * @method static EloquentBuilder|TransactionJournal newModelQuery()
 * @method static EloquentBuilder|TransactionJournal newQuery()
 * @method static \Illuminate\Database\Query\Builder|TransactionJournal onlyTrashed()
 * @method static EloquentBuilder|TransactionJournal query()
 * @method static EloquentBuilder|TransactionJournal transactionTypes($types)
 * @method static EloquentBuilder|TransactionJournal whereBillId($value)
 * @method static EloquentBuilder|TransactionJournal whereBookDate($value)
 * @method static EloquentBuilder|TransactionJournal whereCompleted($value)
 * @method static EloquentBuilder|TransactionJournal whereCreatedAt($value)
 * @method static EloquentBuilder|TransactionJournal whereDate($value)
 * @method static EloquentBuilder|TransactionJournal whereDeletedAt($value)
 * @method static EloquentBuilder|TransactionJournal whereDescription($value)
 * @method static EloquentBuilder|TransactionJournal whereEncrypted($value)
 * @method static EloquentBuilder|TransactionJournal whereId($value)
 * @method static EloquentBuilder|TransactionJournal whereInterestDate($value)
 * @method static EloquentBuilder|TransactionJournal whereOrder($value)
 * @method static EloquentBuilder|TransactionJournal whereProcessDate($value)
 * @method static EloquentBuilder|TransactionJournal whereTagCount($value)
 * @method static EloquentBuilder|TransactionJournal whereTransactionCurrencyId($value)
 * @method static EloquentBuilder|TransactionJournal whereTransactionGroupId($value)
 * @method static EloquentBuilder|TransactionJournal whereTransactionTypeId($value)
 * @method static EloquentBuilder|TransactionJournal whereUpdatedAt($value)
 * @method static EloquentBuilder|TransactionJournal whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|TransactionJournal withTrashed()
 * @method static \Illuminate\Database\Query\Builder|TransactionJournal withoutTrashed()
 * @mixin Eloquent
 */
class TransactionJournal extends Model
{
    use SoftDeletes, HasFactory;

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
            'date'          => 'datetime',
            'interest_date' => 'date',
            'book_date'     => 'date',
            'process_date'  => 'date',
            'order'         => 'int',
            'tag_count'     => 'int',
            'encrypted'     => 'boolean',
            'completed'     => 'boolean',
        ];

    /** @var array Fields that can be filled */
    protected $fillable
        = ['user_id', 'transaction_type_id', 'bill_id', 'tag_count', 'transaction_currency_id', 'description', 'completed', 'order',
           'date'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];

    /**
     * Checks if tables are joined.
     *
     * @codeCoverageIgnore
     *
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table): bool
    {
        $joins = $query->getQuery()->joins;
        if (null === $joins) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @throws NotFoundHttpException
     * @throws FireflyException
     * @return TransactionJournal
     */
    public static function routeBinder(string $value): TransactionJournal
    {
        if (auth()->check()) {
            $journalId = (int) $value;
            /** @var User $user */
            $user = auth()->user();
            /** @var TransactionJournal $journal */
            $journal = $user->transactionJournals()->where('transaction_journals.id', $journalId)->first(['transaction_journals.*']);
            if (null !== $journal) {
                return $journal;
            }
        }

        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(Budget::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function destJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'destination_id');
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
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
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
    public function scopeAfter(EloquentBuilder $query, Carbon $date): EloquentBuilder
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
    public function scopeBefore(EloquentBuilder $query, Carbon $date): EloquentBuilder
    {
        return $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeTransactionTypes(EloquentBuilder $query, array $types): void
    {
        if (!self::isJoined($query, 'transaction_types')) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        }
        if (!empty($types)) {
            $query->whereIn('transaction_types.type', $types);
        }
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
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionGroup(): BelongsTo
    {
        return $this->belongsTo(TransactionGroup::class);
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
     * @return BelongsTo
     */
    public function transactionType(): BelongsTo
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
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
