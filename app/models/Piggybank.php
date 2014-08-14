<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;

/**
 * Piggybank
 *
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer        $account_id
 * @property \Carbon\Carbon $targetdate
 * @property string         $name
 * @property float          $amount
 * @property float          $target
 * @property integer        $order
 * @property-read \Account  $account
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetdate($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTarget($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereOrder($value)
 * @property float          $targetamount
 * @property string         $startdate
 * @property boolean        $repeats
 * @property string         $rep_length
 * @property integer        $rep_times
 * @property string         $reminder
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetamount($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepeats($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepLength($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereRepTimes($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereReminder($value)
 */
class Piggybank extends Ardent
{
    public static $rules
        = [
            'account_id'    => 'required|exists:accounts,id',
            'name'          => 'required|between:1,255',
            'targetamount'  => 'required|min:0',
            'targetdate'    => 'date',
            'startdate'     => 'date',
            'repeats'       => 'required|between:0,1',
            'rep_length'    => 'in:day,week,month,year',
            'rep_times'     => 'required|min:0|max:100',
            'reminder'      => 'in:day,week,month,year',
            'reminder_skip' => 'required|min:0|max:100',
            'order'         => 'required:min:1',
        ];
    public $fillable
        = [
            'account_id',
            'name',
            'targetamount',
            'targetdate',
            'startdate',
            'repeats',
            'rep_length',
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
        $start->endOfMonth();
        $today = new Carbon;

        return [
            'account_id'   => 'factory|Account',
            'name'         => 'string',
            'targetamount' => 'required|min:0',
            'targetdate'   => $start,
            'startdate'    => $today,
            'repeats'      => 0,
            'rep_length'   => null,
            'rep_times'    => 0,
            'reminder'     => null,
            'reminder_skip'    => 0,
            'order'        => 1,
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
        return ['created_at', 'updated_at', 'targetdate'];
    }

    public function piggybankrepetitions()
    {
        return $this->hasMany('PiggybankRepetition');
    }

} 