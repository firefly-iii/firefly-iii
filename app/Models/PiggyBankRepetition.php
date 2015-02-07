<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class PiggyBankRepetition extends Model
{

    public function piggyBank()
    {
        return $this->belongsTo('FireflyIII\Models\PiggyBank');
    }
    public function getDates()
    {
        return ['created_at', 'updated_at','startdate','targetdate'];
    }

}
