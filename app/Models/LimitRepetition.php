<?php namespace FireflyIII\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\LimitRepetition
 *
 * @property integer          $id
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 * @property integer          $budget_limit_id
 * @property \Carbon\Carbon   $startdate
 * @property \Carbon\Carbon   $enddate
 * @property float            $amount
 * @property-read BudgetLimit $budgetLimit
 * @property int              $budget_id
 */
class LimitRepetition extends Model
{

    protected $dates  = ['created_at', 'updated_at', 'startdate', 'enddate'];
    protected $hidden = ['amount_encrypted'];

    /**
     * @param $value
     *
     * @return mixed
     */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetLimit()
    {
        return $this->belongsTo('FireflyIII\Models\BudgetLimit');
    }

    /**
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 2));
    }

}
