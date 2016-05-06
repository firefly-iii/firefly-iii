<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereBudgetLimitId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereEnddate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LimitRepetition whereAmount($value)
 * @mixin \Eloquent
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
     *
     * @param Builder $query
     * @param Carbon  $date
     *
     */
    public function scopeAfter(Builder $query, Carbon $date)
    {
        $query->where('limit_repetitions.startdate', '>=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     *
     * @param Builder $query
     * @param Carbon  $date
     *
     */
    public function scopeBefore(Builder $query, Carbon $date)
    {
        $query->where('limit_repetitions.enddate', '<=', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 2));
    }

}
