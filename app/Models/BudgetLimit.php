<?php
/**
 * BudgetLimit.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BudgetLimit.
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'start_date' => 'date',
            'end_date'   => 'date',
            'repeats'    => 'boolean',
        ];

    /**
     * @param string $value
     *
     * @return mixed
     */
    public static function routeBinder(string $value): BudgetLimit
    {
        if (auth()->check()) {
            $budgetLimitId = intval($value);
            $budgetLimit   = self::where('budget_limits.id', $budgetLimitId)
                                 ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                 ->where('budgets.user_id', auth()->user()->id)
                                 ->first(['budget_limits.*']);
            if (!is_null($budgetLimit)) {
                return $budgetLimit;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('FireflyIII\Models\Budget');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 12));
    }
}
