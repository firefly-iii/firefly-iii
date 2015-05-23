<?php namespace FireflyIII\Models;

use Auth;
use Crypt;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LimitRepetition
 *
 * @package FireflyIII\Models
 */
class LimitRepetition extends Model
{

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetLimit()
    {
        return $this->belongsTo('FireflyIII\Models\BudgetLimit');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
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
