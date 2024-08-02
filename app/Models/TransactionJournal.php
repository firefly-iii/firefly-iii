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
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperTransactionJournal
 */
class TransactionJournal extends Model
{
    use HasFactory;
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

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

    protected $fillable
                      = [
            'user_id',
            'user_group_id',
            'transaction_type_id',
            'bill_id',
            'tag_count',
            'transaction_currency_id',
            'description',
            'completed',
            'order',
            'date',
        ];

    protected $hidden = ['encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $journalId = (int)$value;

            /** @var User $user */
            $user      = auth()->user();

            /** @var null|TransactionJournal $journal */
            $journal   = $user->transactionJournals()->where('transaction_journals.id', $journalId)->first(['transaction_journals.*']);
            if (null !== $journal) {
                return $journal;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function auditLogEntries(): MorphMany
    {
        return $this->morphMany(AuditLogEntry::class, 'auditable');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(Budget::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function destJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'destination_id');
    }

    public function isTransfer(): bool
    {
        if (null !== $this->transaction_type_type) {
            return TransactionType::TRANSFER === $this->transaction_type_type;
        }

        return $this->transactionType->isTransfer();
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * Get all the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function piggyBankEvents(): HasMany
    {
        return $this->hasMany(PiggyBankEvent::class);
    }

    public function scopeAfter(EloquentBuilder $query, Carbon $date): EloquentBuilder
    {
        return $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    public function scopeBefore(EloquentBuilder $query, Carbon $date): EloquentBuilder
    {
        return $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 00:00:00'));
    }

    public function scopeTransactionTypes(EloquentBuilder $query, array $types): void
    {
        if (!self::isJoined($query, 'transaction_types')) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        }
        if (0 !== count($types)) {
            $query->whereIn('transaction_types.type', $types);
        }
    }

    /**
     * Checks if tables are joined.
     */
    public static function isJoined(Builder $query, string $table): bool
    {
        $joins = $query->getQuery()->joins;
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    public function sourceJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class, 'source_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionGroup(): BelongsTo
    {
        return $this->belongsTo(TransactionGroup::class);
    }

    public function transactionJournalMeta(): HasMany
    {
        return $this->hasMany(TransactionJournalMeta::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function transactionTypeId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
