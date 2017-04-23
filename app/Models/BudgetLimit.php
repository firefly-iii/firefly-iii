<?php
/**
 * BudgetLimit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BudgetLimit
 *
 * @package FireflyIII\Models
 */
class BudgetLimit extends Model
{

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'start_date' => 'date',
            'end_date'   => 'date',
            'repeats'    => 'boolean',
        ];
    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'start_date', 'end_date'];

    /**
     * @param $value
     *
     * @return mixed
     */
    public static function routeBinder($value)
    {
        if (auth()->check()) {
            $object = self::where('budget_limits.id', $value)
                          ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                          ->where('budgets.user_id', auth()->user()->id)
                          ->first(['budget_limits.*']);
            if ($object) {
                return $object;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('FireflyIII\Models\Budget');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function limitrepetitions()
    {
        return $this->hasMany('FireflyIII\Models\LimitRepetition');
    }


    /**
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 12));
    }

}
