<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankRepetition');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reminders()
    {
        return $this->morphMany('FireflyIII\Models\Reminder', 'remindersable');
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns PiggyBankRepetition
     */
    public function currentRelevantRep()
    {
        if ($this->currentRep) {
            return $this->currentRep;
        }
        if ($this->repeats == 0) {
            $rep              = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
            $this->currentRep = $rep;

            return $rep;
        } else {
            $query  = $this->piggyBankRepetitions()->where(
                function (EloquentBuilder $q) {

                    $q->where(
                        function (EloquentBuilder $q) {

                            $q->where(
                                function (EloquentBuilder $q) {
                                    $today = new Carbon;
                                    $q->whereNull('startdate');
                                    $q->orWhere('startdate', '<=', $today->format('Y-m-d 00:00:00'));
                                }
                            )->where(
                                function (EloquentBuilder $q) {
                                    $today = new Carbon;
                                    $q->whereNull('targetdate');
                                    $q->orWhere('targetdate', '>=', $today->format('Y-m-d 00:00:00'));
                                }
                            );
                        }
                    )->orWhere(
                        function (EloquentBuilder $q) {
                            $today = new Carbon;
                            $q->where('startdate', '>=', $today->format('Y-m-d 00:00:00'));
                            $q->where('targetdate', '>=', $today->format('Y-m-d 00:00:00'));
                        }
                    );

                }
            )->orderBy('startdate', 'ASC');
            $result = $query->first(['piggy_bank_repetitions.*']);
            $this->currentRep = $result;

            return $result;
        }


    }
}
