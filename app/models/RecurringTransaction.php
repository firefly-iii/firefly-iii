<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent;

/**
 * RecurringTransaction
 *
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer        $user_id
 * @property string         $name
 * @property string         $match
 * @property float          $amount_max
 * @property float          $amount_min
 * @property \Carbon\Carbon $date
 * @property boolean        $active
 * @property boolean        $automatch
 * @property string         $repeat_freq
 * @property integer        $skip
 * @property-read \User     $user
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereMatch($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereAmountMax($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransaction whereAmountMin($value)
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


    /**
     * @return Carbon
     */
    public function next()
    {
        $today = new Carbon;
        $start = clone $this->date;
        $skip  = $this->skip == 0 ? 1 : $this->skip;
        if ($today < $start) {
            return $start;
        }

        while ($start <= $this->date) {
            switch ($this->repeat_freq) {
                case 'daily':
                    $start->addDays($skip);
                    break;
                case 'weekly':
                    $start->addWeeks($skip);
                    break;
                case 'monthly':
                    $start->addMonths($skip);
                    break;
                case 'quarterly':
                    $start->addMonths($skip * 3);
                    break;
                case 'half-year':
                    $start->addMonths($skip * 6);
                    break;
                case 'yearly':
                    $start->addYears($skip);
                    break;

            }
        }

        return $start;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->hasMany('TransactionJournal');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }
} 