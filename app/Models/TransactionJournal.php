<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionJournal extends Model
{

    public function bill()
    {
        return $this->belongsTo('Bill');
    }

    public function budgets()
    {
        return $this->belongsToMany('Budget');
    }

    public function categories()
    {
        return $this->belongsToMany('Category');
    }

    public function getDescriptionAttribute($value)
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        // @codeCoverageIgnoreStart
        return $value;
        // @codeCoverageIgnoreEnd
    }

    public function piggyBankEvents()
    {
        return $this->hasMany('PiggyBankEvent');
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
        $this->attributes['encrypted']   = true;
    }

    public function transactionCurrency()
    {
        return $this->belongsTo('TransactionCurrency');
    }

    public function transactionType()
    {
        return $this->belongsTo('TransactionType');
    }

    public function transactiongroups()
    {
        return $this->belongsToMany('TransactionGroup');
    }

    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }


}
