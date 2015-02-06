<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class PiggyBank extends Model
{

    public function account()
    {
        return $this->belongsTo('Account');
    }

    public function piggyBankEvents()
    {
        return $this->hasMany('PiggyBankEvent');
    }

    public function piggyBankRepetitions()
    {
        return $this->hasMany('PiggyBankRepetition');
    }

    public function reminders()
    {
        return $this->morphMany('Reminder', 'remindersable');
    }
}
