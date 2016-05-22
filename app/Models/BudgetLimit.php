<?php
/**
 * BudgetLimit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\BudgetLimit
 *
 * @property integer                                                         $id
 * @property \Carbon\Carbon                                                  $created_at
 * @property \Carbon\Carbon                                                  $updated_at
 * @property integer                                                         $budget_id
 * @property \Carbon\Carbon                                                  $startdate
 * @property float                                                           $amount
 * @property boolean                                                         $repeats
 * @property string                                                          $repeat_freq
 * @property-read Budget                                                     $budget
 * @property int                                                             $component_id
 * @property-read \Illuminate\Database\Eloquent\Collection|LimitRepetition[] $limitrepetitions
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereBudgetId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereRepeats($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereRepeatFreq($value)
 * @mixin \Eloquent
 */
class BudgetLimit extends Model
{

    protected $dates  = ['created_at', 'updated_at', 'startdate'];
    protected $hidden = ['amount_encrypted'];

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
        $this->attributes['amount'] = strval(round($value, 2));
    }

}
