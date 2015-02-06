<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{

    public function remindersable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo('User');
    }


}
