<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * FireflyIII\Models\BudgetLimit
 *
 * @property integer                           $id
 * @property Carbon                            $created_at
 * @property Carbon                            $updated_at
 * @property integer                           $budget_id
 * @property Carbon                            $startdate
 * @property float                             $amount
 * @property boolean                           $repeats
 * @property string                            $repeat_freq
 * @property-read Budget                       $budget
 * @property-read Collection|LimitRepetition[] $limitrepetitions
 */
class BudgetLimit extends Model
{

    protected $hidden = ['amount_encrypted'];
    protected $date = ['created_at', 'updated_at', 'startdate'];

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
