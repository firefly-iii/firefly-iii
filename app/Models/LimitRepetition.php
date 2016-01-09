<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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


    public static function routeBinder($value)
    {
        if (Auth::check()) {
            $object = LimitRepetition::where('limit_repetitions.id', $value)
                                     ->leftjoin('budget_limits', 'budget_limits.id', '=', 'limit_repetitions.budget_limit_id')
                                     ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                     ->where('budgets.user_id', Auth::user()->id)
                                     ->first(['limit_repetitions.*']);
            if ($object) {
                return $object;
            }
        }
        throw new NotFoundHttpException;
    }

}
