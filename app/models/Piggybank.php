<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;

/**
 * Piggybank
 *
 * @property integer                                                              $id
 * @property \Carbon\Carbon                                                       $created_at
 * @property \Carbon\Carbon                                                       $updated_at
 * @property integer                                                              $account_id
 * @property string                                                               $name
 * @property float                                                                $targetamount
 * @property \Carbon\Carbon                                                       $targetdate
 * @property \Carbon\Carbon                                                       $startdate
 * @property boolean                                                              $repeats
 * @property string                                                               $rep_length
 * @property integer                                                              $rep_every
 * @property integer                                                              $rep_times
 * @property string                                                               $reminder
 * @property integer                                                              $reminder_skip
 * @property integer                                                              $order
 * @property-read \Account                                                        $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\PiggybankRepetition[] $piggybankrepetitions
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetamount($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetdate($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepeats($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepLength($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepEvery($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepTimes($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereReminder($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereReminderSkip($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereOrder($value)
 */
class Piggybank extends Ardent
{
    public static $rules
        = [
            'account_id'    => 'required|exists:accounts,id', // link to Account
            'name'          => 'required|between:1,255', // name
            'targetamount'  => 'required|min:0', // amount you want to save
            'startdate'     => 'date', // when you started
            'targetdate'    => 'date', // when its due
            'repeats'       => 'required|between:0,1', // does it repeat?
            'rep_length'    => 'in:day,week,month,year', // how long is the period?
            'rep_every'     => 'required|min:1|max:100', // how often does it repeat? every 3 years.
            'rep_times'     => 'min:1|max:100', // how many times do you want to save this amount? eg. 3 times
            'reminder'      => 'in:day,week,month,year', // want a reminder to put money in this?
            'reminder_skip' => 'required|min:0|max:100', // every week? every 2 months?
            'order'         => 'required:min:1', // not yet used.
        ];
    public $fillable
        = [
            'account_id',
            'name',
            'targetamount',
            'startdate',
            'targetdate',
            'repeats',
            'rep_length',
            'rep_every',
            'rep_times',
            'reminder',
            'reminder_skip',
            'order'
        ];

    /**
     * @return array
     */
    public static function factory()
    {
        $start = new Carbon;
        $start->startOfMonth();
        $end = new Carbon;
        $end->endOfMonth();

        return [
            'account_id'    => 'factory|Account',
            'name'          => 'string',
            'targetamount'  => 'integer',
            'startdate'     => $start->format('Y-m-d'),
            'targetdate'    => $end->format('Y-m-d'),
            'repeats'       => 0,
            'rep_length'    => null,
            'rep_times'     => 0,
            'rep_every'     => 0,
            'reminder'      => null,
            'reminder_skip' => 0,
            'order'         => 1,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'targetdate', 'startdate'];
    }

    /**
     * Firefly shouldn't create piggybank repetions that completely
     * lie in the future, so we should be able to safely grab the "latest"
     * one and use that to calculate when the user will be reminded.
     */
    public function nextReminderDate()
    {
        if (is_null($this->reminder)) {
            return null;
        }
        /** @var \PiggybankRepetition $rep */
        $rep = $this->currentRelevantRep();
        $today = new Carbon;
        if ($rep && is_null($rep->startdate)) {
            switch ($this->reminder) {
                case 'day':
                    return $today;
                    break;
                case 'week':
                    return $today->endOfWeek();
                    break;
                case 'month':
                    return $today->endOfMonth();
                    break;
                case 'year':
                    return $today->endOfYear();
                    break;

            }
            return null;
        }
        if ($rep && !is_null($rep->startdate)) {
            // start with the start date
            // when its bigger than today, return it:
            $start = clone $rep->startdate;
            while ($start <= $today) {
                switch ($this->reminder) {
                    default:
                        return null;
                        break;
                    case 'day':
                        $start->addDay();
                        break;
                    case 'week':
                        $start->addWeek();
                        break;
                    case 'month':
                        $start->addMonth();
                        break;
                    case 'year':
                        $start->addYear();
                        break;

                }
            }

            return $start;
        }

        return new Carbon;
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns \PiggybankRepetition
     */
    public function currentRelevantRep()
    {
        $query = $this->piggybankrepetitions()
            ->where(
                function ($q) {
                    $today = new Carbon;
                    $q->whereNull('startdate');
                    $q->orWhere('startdate', '<=', $today->format('Y-m-d'));
                }
            )
            ->where(
                function ($q) {
                    $today = new Carbon;
                    $q->whereNull('targetdate');
                    $q->orWhere('targetdate', '>=', $today->format('Y-m-d'));
                }
            );
        $result = $query->first();

        return $result;


    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggybankrepetitions()
    {
        return $this->hasMany('PiggybankRepetition');
    }

} 