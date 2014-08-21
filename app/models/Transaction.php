<?php

use LaravelBook\Ardent\Ardent;


/**
 * Transaction
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $account_id
 * @property integer $piggybank_id
 * @property integer $transaction_journal_id
 * @property string $description
 * @property float $amount
 * @property-read \Account $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\Budget[] $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\Component[] $components
 * @property-read \Piggybank $piggybank
 * @property-read \TransactionJournal $transactionJournal
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereAccountId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction wherePiggybankId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereTransactionJournalId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereDescription($value) 
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereAmount($value) 
 */
class Transaction extends Ardent
{
    public static $rules
        = [
            'account_id'             => 'numeric|required|exists:accounts,id',
            'piggybank_id'           => 'numeric|exists:piggybanks,id',
            'transaction_journal_id' => 'numeric|required|exists:transaction_journals,id',
            'description'            => 'between:1,255',
            'amount'                 => 'required|between:-65536,65536|not_in:0,0.00',
        ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function budgets()
    {
        return $this->belongsToMany('Budget', 'component_transaction', 'transaction_id', 'component_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany('Category', 'component_transaction', 'transaction_id', 'component_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function components()
    {
        return $this->belongsToMany('Component');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }
} 