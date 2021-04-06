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
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FireflyIII\Models\Transaction
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $reconciled
 * @property int $account_id
 * @property int $transaction_journal_id
 * @property string|null $description
 * @property int|null $transaction_currency_id
 * @property string $modified
 * @property string $modified_foreign
 * @property string $date
 * @property string $max_date
 * @property string $amount
 * @property string|null $foreign_amount
 * @property int|null $foreign_currency_id
 * @property int $identifier
 * @property-read \FireflyIII\Models\Account $account
 * @property-read Collection|\FireflyIII\Models\Budget[] $budgets
 * @property-read int|null $budgets_count
 * @property-read Collection|\FireflyIII\Models\Category[] $categories
 * @property-read int|null $categories_count
 * @property-read \FireflyIII\Models\TransactionCurrency|null $foreignCurrency
 * @property-read \FireflyIII\Models\TransactionCurrency|null $transactionCurrency
 * @property-read \FireflyIII\Models\TransactionJournal $transactionJournal
 * @method static Builder|Transaction after(\Carbon\Carbon $date)
 * @method static Builder|Transaction before(\Carbon\Carbon $date)
 * @method static Builder|Transaction newModelQuery()
 * @method static Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Query\Builder|Transaction onlyTrashed()
 * @method static Builder|Transaction query()
 * @method static Builder|Transaction transactionTypes($types)
 * @method static Builder|Transaction whereAccountId($value)
 * @method static Builder|Transaction whereAmount($value)
 * @method static Builder|Transaction whereCreatedAt($value)
 * @method static Builder|Transaction whereDeletedAt($value)
 * @method static Builder|Transaction whereDescription($value)
 * @method static Builder|Transaction whereForeignAmount($value)
 * @method static Builder|Transaction whereForeignCurrencyId($value)
 * @method static Builder|Transaction whereId($value)
 * @method static Builder|Transaction whereIdentifier($value)
 * @method static Builder|Transaction whereReconciled($value)
 * @method static Builder|Transaction whereTransactionCurrencyId($value)
 * @method static Builder|Transaction whereTransactionJournalId($value)
 * @method static Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Transaction withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Transaction withoutTrashed()
 * @mixin Eloquent
 */
class Transaction extends Model
{
    use SoftDeletes, HasFactory;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
            'deleted_at'          => 'datetime',
            'identifier'          => 'int',
            'encrypted'           => 'boolean', // model does not have these fields though
            'bill_name_encrypted' => 'boolean',
            'reconciled'          => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable
        = ['account_id', 'transaction_journal_id', 'description', 'amount', 'identifier', 'transaction_currency_id', 'foreign_currency_id',
           'foreign_amount', 'reconciled'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];

    /**
     * Check if a table is joined.
     *
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     * @codeCoverageIgnore
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
     * Get the account this object belongs to.
     *
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the budget(s) this object belongs to.
     *
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(Budget::class);
    }

    /**
     * Get the category(ies) this object belongs to.
     *
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the currency this object belongs to.
     *
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function foreignCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class, 'foreign_currency_id');
    }

    /**
     * Check for transactions AFTER a specified date.
     *
     * @codeCoverageIgnore
     *
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeAfter(Builder $query, Carbon $date): void
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }
        $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * Check for transactions BEFORE the specified date.
     *
     * @codeCoverageIgnore
     *
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeBefore(Builder $query, Carbon $date): void
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }
        $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Builder $query
     * @param array   $types
     */
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
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = (string) $value;
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
    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class);
    }
}
