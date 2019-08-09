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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Class TransactionJournal.
 *
 * @property User                                                                                      $user
 * @property int                                                                                       $bill_id
 * @property Collection                                                                                $categories
 * @property bool                                                                                      $completed
 * @property string                                                                                    $description
 * @property int                                                                                       $transaction_type_id
 * @property int                                                                                       transaction_currency_id
 * @property TransactionCurrency                                                                       $transactionCurrency
 * @property Collection                                                                                $tags
 * @property int                                                                                       user_id
 * @property Collection                                                                                transactions
 * @property int                                                                                       transaction_count
 * @property Carbon                                                                                    interest_date
 * @property Carbon                                                                                    book_date
 * @property Carbon                                                                                    process_date
 * @property bool                                                                                      encrypted
 * @property int                                                                                       order
 * @property int                                                                                       budget_id
 * @property string                                                                                    period_marker
 * @property Carbon                                                                                    $date
 * @property string                                                                                    $transaction_type_type
 * @property int                                                                                       $id
 * @property TransactionType                                                                           $transactionType
 * @property Collection                                                                                budgets
 * @property Bill                                                                                      $bill
 * @property Collection                                                                                transactionJournalMeta
 * @property TransactionGroup                                                                          transactionGroup
 * @property int                                                                                       transaction_group_id
 * @SuppressWarnings (PHPMD.TooManyPublicMethods)
 * @SuppressWarnings (PHPMD.CouplingBetweenObjects)
 * @property \Illuminate\Support\Carbon|null                                                           $created_at
 * @property \Illuminate\Support\Carbon|null                                                           $updated_at
 * @property \Illuminate\Support\Carbon|null                                                           $deleted_at
 * @property int                                                                                       $tag_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[]             $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Note[]                   $notes
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\PiggyBankEvent[]         $piggyBankEvents
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournalLink[] $sourceJournalLinks
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[]               $transactionGroups
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal after(\Carbon\Carbon $date)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal before(\Carbon\Carbon $date)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal transactionTypes($types)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereBookDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereInterestDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereProcessDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereTagCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereTransactionTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\TransactionJournal whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionJournal withoutTrashed()
 * @mixin \Eloquent
 */
class TransactionJournal extends Model
{
    use SoftDeletes;

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
        = ['user_id', 'transaction_type_id', 'bill_id', 'tag_count','transaction_currency_id', 'description', 'completed', 'order',
           'date'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];

    /**
     * Checks if tables are joined.
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
     * @return TransactionJournal
     * @throws NotFoundHttpException
     * @throws FireflyException
     */
    public static function routeBinder(string $value): TransactionJournal
    {
        if (auth()->check()) {
            $journalId = (int)$value;
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
        if (count($types) > 0) {
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
