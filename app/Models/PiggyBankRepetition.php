<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class PiggyBankRepetition extends Model
{

    public function piggyBank()
    {
        return $this->belongsTo('PiggyBank');
    }

}
