<?php

/**
 * Transaction.php
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
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use Cachable;
    use HasFactory;
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $casts
                      = [
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
            'deleted_at'          => 'datetime',
            'identifier'          => 'int',
            'encrypted'           => 'boolean', // model does not have these fields though
            'bill_name_encrypted' => 'boolean',
            'reconciled'          => 'boolean',
            'balance_dirty'       => 'boolean',
            'date'                => 'datetime',
        ];

    protected $fillable
                      = [
            'account_id',
            'transaction_journal_id',
            'description',
            'amount',
            'identifier',
            'transaction_currency_id',
            'foreign_currency_id',
            'foreign_amount',
            'reconciled',
        ];

    protected $hidden = ['encrypted'];

    /**
     * Get the account this object belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the budget(s) this object belongs to.
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(Budget::class);
    }

    /**
     * Get the category(ies) this object belongs to.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the currency this object belongs to.
     */
    public function foreignCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class, 'foreign_currency_id');
    }

    /**
     * Check for transactions AFTER a specified date.
     */
    public function scopeAfter(Builder $query, Carbon $date): void
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }
        $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * Check if a table is joined.
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

    /**
     * Check for transactions BEFORE the specified date.
     */
    public function scopeBefore(Builder $query, Carbon $date): void
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }
        $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'));
    }

    public function scopeTransactionTypes(Builder $query, array $types): void
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }

        if (!self::isJoined($query, 'transaction_types')) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        }
        $query->whereIn('transaction_types.type', $types);
    }

    /**
     * @param mixed $value
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = (string)$value;
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class);
    }

    protected function accountId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function balanceDirty(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => 1 === (int)$value,
        );
    }

    /**
     * Get the amount
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    /**
     * Get the foreign amount
     */
    protected function foreignAmount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    protected function transactionJournalId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
