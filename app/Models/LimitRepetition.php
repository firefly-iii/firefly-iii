<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LimitRepetition
 *
 * @package FireflyIII\Models
 * @property integer                             $id
 * @property \Carbon\Carbon                      $created_at
 * @property \Carbon\Carbon                      $updated_at
 * @property integer                             $budget_limit_id
 * @property \Carbon\Carbon                      $startdate
 * @property \Carbon\Carbon                      $enddate
 * @property float                               $amount
 * @property-read \FireflyIII\Models\BudgetLimit $budgetLimit
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereBudgetLimitId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereEnddate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereAmount($value)
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
