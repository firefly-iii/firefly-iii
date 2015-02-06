<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionJournal extends Model
{

    public function bill()
    {
        return $this->belongsTo('FireflyIII\Models\Bill');
    }

    public function budgets()
    {
        return $this->belongsToMany('FireflyIII\Models\Budget');
    }

    public function categories()
    {
        return $this->belongsToMany('FireflyIII\Models\Category');
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
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = \Crypt::encrypt($value);
        $this->attributes['encrypted']   = true;
    }

    public function transactionCurrency()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionCurrency');
    }

    public function transactionType()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionType');
    }

    public function transactiongroups()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionGroup');
    }

    public function transactions()
    {
        return $this->hasMany('FireflyIII\Models\Transaction');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }


}
