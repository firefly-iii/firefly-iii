<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent;


/**
 * RecurringTransaction
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $user_id
 * @property string $name
 * @property string $match
 * @property float $amount_min
 * @property float $amount_max
 * @property \Carbon\Carbon $date
 * @property boolean $active
 * @property boolean $automatch
 * @property string $repeat_freq
 * @property integer $skip
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 * @property-read \User $user
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereMatch($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereAmountMin($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereAmountMax($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereAutomatch($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereRepeatFreq($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereSkip($value)
 */
class RecurringTransaction extends Ardent
{

    public static $rules
        = ['user_id'     => 'required|exists:users,id', 'name' => 'required|between:1,255', 'match' => 'required', 'amount_max' => 'required|between:0,65536',
           'amount_min'  => 'required|between:0,65536', 'date' => 'required|date', 'active' => 'required|between:0,1', 'automatch' => 'required|between:0,1',
           'repeat_freq' => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly', 'skip' => 'required|between:0,31',];

    protected $fillable = ['user_id', 'name', 'match', 'amount_min', 'amount_max', 'date', 'repeat_freq', 'skip', 'active', 'automatch'];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    public function lastFoundMatch()
    {
        $last = $this->transactionjournals()->orderBy('date', 'DESC')->first();
        if ($last) {
            return $last->date;
        }

        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->hasMany('TransactionJournal');
    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * Find the next expected match based on the set journals and the date stuff from the recurring
     * transaction.
     */
    public function nextExpectedMatch()
    {

        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
        $dateKit = App::make('FireflyIII\Shared\Toolkit\Date');

        /*
         * The date Firefly tries to find. If this stays null, it's "unknown".
         */
        $finalDate = null;

        /*
         * $today is the start of the next period, to make sure FF3 won't miss anything
         * when the current period has a transaction journal.
         */
        $today = $dateKit->addPeriod(new Carbon, $this->repeat_freq, 0);

        /*
         * FF3 loops from the $start of the recurring transaction, and to make sure
         * $skip works, it adds one (for modulo).
         */
        $skip  = $this->skip + 1;
        $start = $dateKit->startOfPeriod(new Carbon, $this->repeat_freq);
        /*
         * go back exactly one month/week/etc because FF3 does not care about 'next'
         * recurring transactions if they're too far into the past.
         */
        //        echo 'Repeat freq is: ' . $recurringTransaction->repeat_freq . '<br />';

        //        echo 'Start: ' . $start . ' <br />';

        $counter = 0;
        while ($start <= $today) {
            if (($counter % $skip) == 0) {
                // do something.
                $end          = $dateKit->endOfPeriod(clone $start, $this->repeat_freq);
                $journalCount = $this->transactionjournals()->before($end)->after($start)->count();
                if ($journalCount == 0) {
                    $finalDate = clone $start;
                    break;
                }
            }

            // add period for next round!
            $start = $dateKit->addPeriod($start, $this->repeat_freq, 0);
            $counter++;
        }

        return $finalDate;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }
} 