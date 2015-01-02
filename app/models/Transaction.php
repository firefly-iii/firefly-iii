<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class Transaction
 */
class Transaction extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;
    public static $rules
        = ['account_id'             => 'numeric|required|exists:accounts,id',
           'transaction_journal_id' => 'numeric|required|exists:transaction_journals,id',
           'description'            => 'between:1,255',
           'amount'                 => 'required|between:-65536,65536|not_in:0,0.00',];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
    }

    /**
     * @param Builder $query
     * @param Account $account
     */
    public function scopeAccountIs(Builder $query, Account $account)
    {
        $query->where('transactions.account_id', $account->id);
    }

    /**
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeAfter(Builder $query, Carbon $date)
    {
        if (is_null($this->joinedJournals)) {
            $query->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            );
            $this->joinedJournals = true;
        }
        $query->where('transaction_journals.date', '>=', $date->format('Y-m-d'));
    }

    /**
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeBefore(Builder $query, Carbon $date)
    {
        if (is_null($this->joinedJournals)) {
            $query->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            );
            $this->joinedJournals = true;
        }
        $query->where('transaction_journals.date', '<=', $date->format('Y-m-d'));
    }

    /**
     * @param Builder $query
     * @param         $amount
     */
    public function scopeLessThan(Builder $query, $amount)
    {
        $query->where('amount', '<', $amount);
    }

    /**
     * @param Builder $query
     * @param         $amount
     */
    public function scopeMoreThan(Builder $query, $amount)
    {
        $query->where('amount', '>', $amount);
    }

    /**
     * @param Builder $query
     * @param array   $types
     */
    public function scopeTransactionTypes(Builder $query, array $types)
    {
        if (is_null($this->joinedJournals)) {
            $query->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            );
            $this->joinedJournals = true;
        }
        if (is_null($this->joinedTransactionTypes)) {
            $query->leftJoin(
                'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
            );
            $this->joinedTransactionTypes = true;
        }
        $query->whereIn('transaction_types.type', $types);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }
} 
