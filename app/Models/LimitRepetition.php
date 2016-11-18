<?php
/**
 * LimitRepetition.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        if (auth()->check()) {
            $object = LimitRepetition::where('limit_repetitions.id', $value)
                                     ->leftJoin('budget_limits', 'budget_limits.id', '=', 'limit_repetitions.budget_limit_id')
                                     ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                     ->where('budgets.user_id', auth()->user()->id)
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
