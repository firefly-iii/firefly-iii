<?php


class Account extends Eloquent
{

    public function accountType()
    {
        return $this->belongsTo('AccountType');
    }

    public function transactions()
    {
        return $this->hasMany('Transaction');
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
        return floatval($this->transactions()->sum('amount'));
    }

} 