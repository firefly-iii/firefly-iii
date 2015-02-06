<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLimit extends Model
{

    public function budget()
    {
        return $this->belongsTo('Budget');
    }

    public function limitrepetitions()
    {
        return $this->hasMany('LimitRepetition');
    }

}
