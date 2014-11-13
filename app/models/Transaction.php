<?php

use Carbon\Carbon;
use FireflyIII\Exception\NotImplementedException;
use LaravelBook\Ardent\Ardent;
use LaravelBook\Ardent\Builder;


/**
 * Transaction
 *
 * @property integer                                                    $id
 * @property \Carbon\Carbon                                             $created_at
 * @property \Carbon\Carbon                                             $updated_at
 * @property integer                                                    $account_id
 * @property integer                                                    $piggybank_id
 * @property integer                                                    $transaction_journal_id
 * @property string                                                     $description
 * @property float                                                      $amount
 * @property-read \Account                                              $account
 * @property-read \Piggybank                                            $piggybank
 * @property-read \Illuminate\Database\Eloquent\Collection|\Budget[]    $budgets
 * @property-read \Illuminate\Database\Eloquent\Collection|\Category[]  $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\Component[] $components
 * @property-read \TransactionJournal                                   $transactionJournal
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction wherePiggybankId($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereTransactionJournalId($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\Transaction whereAmount($value)
 * @method static \Transaction accountIs($account)
 * @method static \Transaction after($date)
 * @method static \Transaction before($date)
 * @method static \Transaction lessThan($amount)
 * @method static \Transaction moreThan($amount)
 * @method static \Transaction transactionTypes($types)
 */
class Transaction extends Ardent
{
    public static $rules
        = ['account_id'             => 'numeric|required|exists:accounts,id', 'piggybank_id' => 'numeric|exists:piggybanks,id',
           'transaction_journal_id' => 'numeric|required|exists:transaction_journals,id', 'description' => 'between:1,255',
           'amount'                 => 'required|between:-65536,65536|not_in:0,0.00',];


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
     * @param Piggybank $piggybank
     *
     * @return bool
     */
    public function connectPiggybank(\Piggybank $piggybank = null)
    {
        // TODO connect a piggy bank to a transaction.
        throw new NotImplementedException;
        //        if (is_null($piggybank)) {
        //            return true;
        //        }
        //        /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggyRepository */
        //        $piggyRepository = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');
        //        if ($this->account_id == $piggybank->account_id) {
        //            $this->piggybank()->associate($piggybank);
        //            $this->save();
        //            \Event::fire('piggybanks.createRelatedTransfer', [$piggybank, $this->transactionJournal, $this]);
        //            return true;
        //        }
        //        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }

    public function scopeAccountIs(Builder $query, Account $account)
    {
        $query->where('transactions.account_id', $account->id);
    }

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

    public function scopeLessThan(Builder $query, $amount)
    {
        $query->where('amount', '<', $amount);
    }

    public function scopeMoreThan(Builder $query, $amount)
    {
        $query->where('amount', '>', $amount);
    }

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