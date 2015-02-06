<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class PiggyBank extends Model
{

    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    public function piggyBankRepetitions()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankRepetition');
    }

    public function reminders()
    {
        return $this->morphMany('FireflyIII\Models\Reminder', 'remindersable');
    }
}
