<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class LimitRepetition extends Model
{

    public function budgetLimit()
    {
        return $this->belongsTo('BudgetLimit');
    }

}
