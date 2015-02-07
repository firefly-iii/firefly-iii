<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLimit extends Model
{

    public function budget()
    {
        return $this->belongsTo('FireflyIII\Models\Budget');
    }

    public function limitrepetitions()
    {
        return $this->hasMany('FireflyIII\Models\LimitRepetition');
    }
    public function getDates()
    {
        return ['created_at', 'updated_at','startdate'];
    }

}
