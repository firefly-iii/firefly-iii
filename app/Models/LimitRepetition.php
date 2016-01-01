<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\LimitRepetition
 *
 * @property integer          $id
 * @property Carbon           $created_at
 * @property Carbon           $updated_at
 * @property integer          $budget_limit_id
 * @property Carbon           $startdate
 * @property Carbon           $enddate
 * @property float            $amount
 * @property-read BudgetLimit $budgetLimit
 */
class LimitRepetition extends Model
{

    protected $hidden = ['amount_encrypted'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetLimit()
    {
        return $this->belongsTo('FireflyIII\Models\BudgetLimit');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 2));
    }

}
