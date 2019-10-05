<?php
/**
 * Transaction.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Transaction.
 *
 * @property int                 $journal_id
 * @property Carbon              $date
 * @property string              $transaction_description
 * @property string              $transaction_amount
 * @property string              $transaction_foreign_amount
 * @property string              $transaction_type_type
 * @property string              $foreign_currency_symbol
 * @property int                 $foreign_currency_dp
 * @property int                 $account_id
 * @property string              $account_name
 * @property string              $account_iban
 * @property string              $account_number
 * @property string              $account_bic
 * @property string              $account_type
 * @property string              $account_currency_code
 * @property int                 $opposing_account_id
 * @property string              $opposing_account_name
 * @property string              $opposing_account_iban
 * @property string              $opposing_account_number
 * @property string              $opposing_account_bic
 * @property string              $opposing_account_type
 * @property string              $opposing_currency_code
 * @property int                 $transaction_budget_id
 * @property string              $transaction_budget_name
 * @property int                 $transaction_journal_budget_id
 * @property string              $transaction_journal_budget_name
 * @property int                 $transaction_category_id
 * @property string              $transaction_category_name
 * @property int                 $transaction_journal_category_id
 * @property string              $transaction_journal_category_name
 * @property int                 $bill_id
 * @property string              $bill_name
 * @property string              $bill_name_encrypted
 * @property string              $notes
 * @property string              $tags
 * @property string              $transaction_currency_name
 * @property string              $transaction_currency_symbol
 * @property int                 $transaction_currency_dp
 * @property string              $transaction_currency_code
 * @property string              $description
 * @property bool                $is_split
 * @property int                 $attachmentCount
 * @property int                 $transaction_currency_id
 * @property int                 $foreign_currency_id
 * @property string              $amount
 * @property string              $foreign_amount
 * @property TransactionJournal  $transactionJournal
 * @property Account             $account
 * @property int                 $identifier
 * @property int                 $id
 * @property TransactionCurrency $transactionCurrency
 * @property int                 $transaction_journal_id
 * @property TransactionCurrency $foreignCurrency
 * @property string              $before      // used in audit reports.
 * @property string              $after       // used in audit reports.
 * @property int                 $opposing_id // ID of the opposing transaction, used in collector
 * @property bool                $encrypted   // is the journal encrypted
 * @property bool                reconciled
 * @property string              transaction_category_encrypted
 * @property string              transaction_journal_category_encrypted
 * @property string              transaction_budget_encrypted
 * @property string              transaction_journal_budget_encrypted
 * @property string              type
 * @property string              name
 * @property Carbon              created_at
 * @property Carbon              updated_at
 * @property string              foreign_currency_code
 * @SuppressWarnings (PHPMD.TooManyPublicMethods)
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[] $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[] $categories
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction after(\Carbon\Carbon $date)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction before(\Carbon\Carbon $date)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction transactionTypes($types)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereForeignAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereForeignCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereReconciled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction withoutTrashed()
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use SoftDeletes;
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
        $this->attributes['amount'] = (string)$value;
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
