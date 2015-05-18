<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PiggyBank
 *
 * @package FireflyIII\Models
 */
class PiggyBank extends Model
{
    use SoftDeletes;

    protected $fillable
        = ['name', 'account_id', 'order', 'reminder_skip', 'targetamount', 'startdate', 'targetdate', 'reminder', 'remind_me'];

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns PiggyBankRepetition
     */
    public function currentRelevantRep()
    {
        if (!is_null($this->currentRep)) {
            return $this->currentRep;
        }
        // repeating piggy banks are no longer supported.
        $rep              = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
        $this->currentRep = $rep;

        return $rep;


    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankRepetition');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return int
     */
    public function getRemindMeAttribute($value)
    {
        return intval($value) == 1;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reminders()
    {
        return $this->morphMany('FireflyIII\Models\Reminder', 'remindersable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        // @codeCoverageIgnoreStart
        return $value;
        // @codeCoverageIgnoreEnd
    }
}
