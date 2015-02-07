<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class LimitRepetition extends Model
{

    public function budgetLimit()
    {
        return $this->belongsTo('FireflyIII\Models\BudgetLimit');
    }

    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

}
