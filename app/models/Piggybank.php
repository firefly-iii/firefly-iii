<?php
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Watson\Validating\ValidatingTrait;

/**
 * Class PiggyBank
 */
class PiggyBank extends Eloquent
{
    use ValidatingTrait;
    protected $rules
        = ['account_id'    => 'required|exists:accounts,id', // link to Account
           'name'          => 'required|between:1,255', // name
           'targetamount'  => 'required|min:0.01|numeric', // amount you want to save
           'startdate'     => 'date', // when you started
           'targetdate'    => 'date', // when its due
           'repeats'       => 'required|boolean', // does it repeat?
           'rep_length'    => 'in:day,week,month,quarter,year', // how long is the period?
           'rep_every'     => 'required|min:1|max:100', // how often does it repeat? every 3 years.
           'rep_times'     => 'min:1|max:100', // how many times do you want to save this amount? eg. 3 times
           'reminder'      => 'in:day,week,quarter,month,year', // want a reminder to put money in this?
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

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @return int
     */
    public function amountPerReminder()
    {
        return 0;

    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @return int
     */
    public function countFutureReminders()
    {
        return 0;
    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @param Carbon $start
     * @param Carbon $target
     *
     * @return PiggyBankRepetition
     */
    public function createRepetition(Carbon $start = null, Carbon $target = null)
    {
        $rep = new \PiggyBankRepetition;
        $rep->piggyBank()->associate($this);
        $rep->startdate     = $start;
        $rep->targetdate    = $target;
        $rep->currentamount = 0;
        $rep->save();

        return $rep;
    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns \PiggyBankRepetition
     */
    public function currentRelevantRep()
    {
        if ($this->currentRep) {
            return $this->currentRep;
        }
        if ($this->repeats == 0) {
            $rep              = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
            $this->currentRep = $rep;
            \Log::debug('currentRelevantRep() reports $rep is null: ' . boolstr(is_null($rep)));

            return $rep;
        } else {
            $query            = $this->piggyBankRepetitions()->where(
                function ($q) {

                    $q->where(
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
                    );

                }
            )
                                     ->orderBy('startdate', 'ASC');
            $result           = $query->first(['piggy_bank_repetitions.*']);
            $this->currentRep = $result;
            \Log::debug('Found relevant rep in currentRelevantRep(): ' . $result->id);

            return $result;
        }


    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany('PiggyBankRepetition');
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
    public function piggyBankEvents()
    {
        return $this->hasMany('PiggyBankEvent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reminders()
    {
        return $this->morphMany('Reminder', 'remindersable');
    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * Same but for specific date.
     *
     * @param Carbon $date
     *
     * @returns \PiggyBankRepetition
     */
    public function repetitionForDate(Carbon $date)
    {
        $query  = $this->piggyBankRepetitions()->where(
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

} 