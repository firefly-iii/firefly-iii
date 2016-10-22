<?php
/**
 * Transaction.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\Transaction
 *
 * @property integer                                                                     $id
 * @property \Carbon\Carbon                                                              $created_at
 * @property \Carbon\Carbon                                                              $updated_at
 * @property \Carbon\Carbon                                                              $deleted_at
 * @property integer                                                                     $account_id
 * @property integer                                                                     $transaction_journal_id
 * @property string                                                                      $description
 * @property float                                                                       $amount
 * @property-read Account                                                                $account
 * @property-read TransactionJournal                                                     $transactionJournal
 * @method static \Illuminate\Database\Query\Builder|Transaction after($date)
 * @method static \Illuminate\Database\Query\Builder|Transaction before($date)
 * @property float                                                                       $before
 * @property float                                                                       $after
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereAmount($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Budget[]   $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Category[] $categories
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction transactionTypes($types)
 * @property integer                                                                     $identifier
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Transaction whereIdentifier($value)
 */
class Transaction extends Model
{

    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['account_id', 'transaction_journal_id', 'description', 'amount', 'identifier'];
    protected $hidden   = ['encrypted'];
    protected $rules
                        = [
            'account_id'             => 'required|exists:accounts,id',
            'transaction_journal_id' => 'required|exists:transaction_journals,id',
            'description'            => 'between:0,1024',
            'amount'                 => 'required|numeric',
        ];

    use SoftDeletes, ValidatingTrait;

    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table):bool
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
        $this->attributes['amount'] = strval(round($value, 2));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }
}
