<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BudgetLimit
 *
 * @package FireflyIII\Models
 */
class BudgetLimit extends Model
{

    protected $hidden = ['amount_encrypted'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('FireflyIII\Models\Budget');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate'];
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
