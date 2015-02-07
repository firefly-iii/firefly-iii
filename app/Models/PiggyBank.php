<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiggyBank extends Model
{
    use SoftDeletes;

    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];
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
