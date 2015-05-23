<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LimitRepetition
 *
 * @codeCoverageIgnore
 *
 * @package FireflyIII\Models
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
     * @param $value
     *
     * @return float|int
     */
    public function getAmountAttribute($value)
    {
        if (is_null($this->amount_encrypted)) {
            return $value;
        }
        $value = intval(Crypt::decrypt($this->amount_encrypted));
        $value = $value / 100;

        return $value;
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
        // save in cents:
        $value                                = intval($value * 100);
        $this->attributes['amount_encrypted'] = Crypt::encrypt($value);
        $this->attributes['amount']           = ($value / 100);
    }

}
