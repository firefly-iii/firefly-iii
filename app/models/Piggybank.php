<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;


/**
 * Piggybank
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $account_id
 * @property string $name
 * @property float $targetamount
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $targetdate
 * @property boolean $repeats
 * @property string $rep_length
 * @property integer $rep_every
 * @property integer $rep_times
 * @property string $reminder
 * @property integer $reminder_skip
 * @property integer $order
 * @property boolean $remind_me
 * @property-read \Account $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\PiggybankRepetition[] $piggybankrepetitions
 * @property-read \Illuminate\Database\Eloquent\Collection|\PiggybankEvent[] $piggybankevents
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[] $transactions
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereAccountId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereName($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetamount($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereStartdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepeats($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepLength($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepEvery($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepTimes($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereReminder($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereReminderSkip($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereOrder($value) 
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRemindMe($value) 
 */
class Piggybank extends Ardent
{
    public static $rules
        = ['account_id'    => 'required|exists:accounts,id', // link to Account
           'name'          => 'required|between:1,255', // name
           'targetamount'  => 'required|min:0', // amount you want to save
           'startdate'     => 'date', // when you started
           'targetdate'    => 'date', // when its due
           'repeats'       => 'required|boolean', // does it repeat?
           'rep_length'    => 'in:day,week,month,year', // how long is the period?
           'rep_every'     => 'required|min:1|max:100', // how often does it repeat? every 3 years.
           'rep_times'     => 'min:1|max:100', // how many times do you want to save this amount? eg. 3 times
           'reminder'      => 'in:day,week,month,year', // want a reminder to put money in this?
           'reminder_skip' => 'required|min:0|max:100', // every week? every 2 months?
           'remind_me'     => 'required|boolean', 'order' => 'required:min:1', // not yet used.
        ];
    public        $fillable
        = ['account_id', 'name', 'targetamount', 'startdate', 'targetdate', 'repeats', 'rep_length', 'rep_every', 'rep_times', 'reminder', 'reminder_skip',
           'remind_me', 'order'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
    }

    public function countFutureReminders()
    {
        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
        $dateKit = App::make('FireflyIII\Shared\Toolkit\Date');

        if (is_null($this->reminder)) {
            return 0;
        }

        $start     = new Carbon;
        $end       = !is_null($this->targetdate) ? clone $this->targetdate : new Carbon;
        $reminders = 0;
        while ($start <= $end) {
            $reminders++;
            $start = $dateKit->addPeriod($start, $this->reminder, $this->reminder_skip);
        }

        return $reminders;
    }

    public function createRepetition(Carbon $start = null, Carbon $target = null)
    {
        $rep = new \PiggybankRepetition;
        $rep->piggybank()->associate($this);
        $rep->startdate     = $start;
        $rep->targetdate    = $target;
        $rep->currentamount = 0;
        $rep->save();
        \Event::fire('piggybanks.repetition', [$rep]);

        return $rep;
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns \PiggybankRepetition
     */
    public function currentRelevantRep()
    {
        if ($this->currentRep) {
            return $this->currentRep;
        }
        if ($this->repeats == 0) {
            $rep              = $this->piggybankrepetitions()->first();
            $this->currentRep = $rep;

            return $rep;
        } else {
            $query            = $this->piggybankrepetitions()->where(
                function ($q) {

                    $q->where(
                        function ($q) {
                            $today = new Carbon;
                            $q->whereNull('startdate');
                            $q->orWhere('startdate', '<=', $today->format('Y-m-d'));
                        }
                    )->where(
                        function ($q) {
                            $today = new Carbon;
                            $q->whereNull('targetdate');
                            $q->orWhere('targetdate', '>=', $today->format('Y-m-d'));
                        }
                    );
                }
            )->orWhere(
                function ($q) {
                    $today = new Carbon;
                    $q->where('startdate', '>=', $today->format('Y-m-d'));
                    $q->where('targetdate', '>=', $today->format('Y-m-d'));
                }
            )->orderBy('startdate', 'ASC');
            $result           = $query->first();
            $this->currentRep = $result;

            return $result;
        }


    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggybankrepetitions()
    {
        return $this->hasMany('PiggybankRepetition');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'targetdate', 'startdate'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggybankevents()
    {
        return $this->hasMany('PiggybankEvent');
    }

    /**
     * Same but for specific date.
     *
     * @param Carbon $date
     *
     * @returns \PiggybankRepetition
     */
    public function repetitionForDate(Carbon $date)
    {
        $query  = $this->piggybankrepetitions()->where(
            function ($q) use ($date) {

                $q->where(
                    function ($q) use ($date) {
                        $q->whereNull('startdate');
                        $q->orWhere('startdate', '<=', $date->format('Y-m-d'));
                    }
                )->where(
                    function ($q) use ($date) {
                        $q->whereNull('targetdate');
                        $q->orWhere('targetdate', '>=', $date->format('Y-m-d'));
                    }
                );
            }
        )->orWhere(
            function ($q) use ($date) {
                $q->where('startdate', '>=', $date->format('Y-m-d'));
                $q->where('targetdate', '>=', $date->format('Y-m-d'));
            }
        )->orderBy('startdate', 'ASC');
        $result = $query->first();

        return $result;
    }

    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

} 