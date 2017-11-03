<?php
/**
 * Transaction.php
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * Class Transaction
 *
 * @property-read int    $journal_id
 * @property Carbon      $date
 * @property-read string $transaction_description
 * @property string      $transaction_amount
 * @property string      $transaction_foreign_amount
 * @property string      $transaction_type_type
 * @property string      $foreign_currency_symbol
 * @property int         $foreign_currency_dp
 *
 * @property int         $account_id
 * @property string      $account_name
 * @property string      $account_iban
 * @property string      $account_number
 * @property string      $account_bic
 * @property string      $account_currency_code
 *
 * @property int         $opposing_account_id
 * @property string      $opposing_account_name
 * @property string      $opposing_account_iban
 * @property string      $opposing_account_number
 * @property string      $opposing_account_bic
 * @property string      $opposing_currency_code
 *
 *
 * @property int         $transaction_budget_id
 * @property string      $transaction_budget_name
 * @property int         $transaction_journal_budget_id
 * @property string      $transaction_journal_budget_name
 *
 * @property-read int    $transaction_category_id
 * @property-read string $transaction_category_name
 * @property-read int    $transaction_journal_category_id
 * @property-read string $transaction_journal_category_name
 *
 * @property-read int    $bill_id
 * @property string      $bill_name
 *
 * @property string      $notes
 * @property string      $tags
 *
 * @property string      $transaction_currency_symbol
 * @property int         $transaction_currency_dp
 * @property string      $transaction_currency_code
 *
 * @package FireflyIII\Models
 */
class Transaction extends Model
{
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
        ];
    protected $fillable
                      = ['account_id', 'transaction_journal_id', 'description', 'amount', 'identifier', 'transaction_currency_id', 'foreign_currency_id',
                         'foreign_amount'];
    protected $hidden = ['encrypted'];
    protected $rules
                      = [
            'account_id'              => 'required|exists:accounts,id',
            'transaction_journal_id'  => 'required|exists:transaction_journals,id',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'description'             => 'between:0,1024',
            'amount'                  => 'required|numeric',
        ];

    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table): bool
    {
        $joins = $query->getQuery()->joins;
        if (is_null($joins)) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    use SoftDeletes, ValidatingTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets()
    {
        return $this->belongsToMany('FireflyIII\Models\Budget');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany('FireflyIII\Models\Category');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function foreignCurrency()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionCurrency', 'foreign_currency_id');
    }

    /**
     * @param $value
     *
     * @return float|int
     */
    public function getAmountAttribute($value)
    {
        return $value;
    }

    /**
     *
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeAfter(Builder $query, Carbon $date)
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }
        $query->where('transaction_journals.date', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     *
     * @param Builder $query
     * @param Carbon  $date
     *
     */
    public function scopeBefore(Builder $query, Carbon $date)
    {
        if (!self::isJoined($query, 'transaction_journals')) {
            $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');
        }
        $query->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'));
    }

    /**
     *
     * @param Builder $query
     * @param array   $types
     */
    public function scopeTransactionTypes(Builder $query, array $types)
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
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 12));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionCurrency()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionCurrency');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }
}
