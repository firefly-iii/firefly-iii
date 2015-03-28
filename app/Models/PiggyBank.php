<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App;
use Log;
/**
 * Class PiggyBank
 *
 * @package FireflyIII\Models
 */
class PiggyBank extends Model
{
    use SoftDeletes;

    protected $fillable
        = ['repeats', 'name', 'account_id', 'rep_every', 'rep_times', 'reminder_skip', 'targetamount', 'startdate', 'targetdate', 'reminder', 'remind_me',
           'rep_length'];

    /**
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
        if (intval($this->repeats) === 0) {
            $rep              = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
            $this->currentRep = $rep;

            return $rep;
        } else {
            Log::error('Tried to work with a piggy bank with a repeats=1 value! (id is '.$this->id.')');
            //App::abort(500);
        }


    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankRepetition');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];
    }

    /**
     * @param $value
     *
     * @return int
     */
    public function getRemindMeAttribute($value)
    {
        return intval($value) == 1;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reminders()
    {
        return $this->morphMany('FireflyIII\Models\Reminder', 'remindersable');
    }
}
