<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BudgetLimit
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Models
 */
class BudgetLimit extends Model
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('FireflyIII\Models\Budget');
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
        // save in cents:
        $value                                = intval($value * 100);
        $this->attributes['amount_encrypted'] = Crypt::encrypt($value);
        $this->attributes['amount']           = ($value / 100);
    }

}
