<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BudgetLimit
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property integer $budget_id 
 * @property \Carbon\Carbon $startdate 
 * @property float $amount 
 * @property string $amount_encrypted 
 * @property boolean $repeats 
 * @property string $repeat_freq 
 * @property-read \FireflyIII\Models\Budget $budget 
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\LimitRepetition[] $limitrepetitions 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereBudgetId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereAmountEncrypted($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereRepeats($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\BudgetLimit whereRepeatFreq($value)
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
