<?php


class Account extends Elegant
{

    public static $rules
        = [
            'name'            => 'required|between:1,100',
            'user_id'         => 'required|exists:users,id',
            'account_type_id' => 'required|exists:account_types,id',
            'active'          => 'required|between:0,1|numeric'

        ];

    public static $factory
        = [
            'name'            => 'string',
            'user_id'         => 'factory|User',
            'account_type_id' => 'factory|AccountType',
            'active'          => '1'
        ];

    public function accountType()
    {
        return $this->belongsTo('AccountType');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }

    /**
     * Get an accounts current balance.
     *
     * @param \Carbon\Carbon $date
     *
     * @return float
     */
    public function balance(\Carbon\Carbon $date = null)
    {
        $date = is_null($date) ? new \Carbon\Carbon : $date;

        return floatval($this->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount'));
    }

    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

} 