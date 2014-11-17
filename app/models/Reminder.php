<?php

use Carbon\Carbon;
use LaravelBook\Ardent\Ardent;

/**
 * Reminder
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $user_id
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $enddate
 * @property boolean $active
 * @property integer $remembersable_id
 * @property string $remembersable_type
 * @property-read \ $remindersable
 * @property-read \User $user
 * @property mixed $data
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereUserId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereStartdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereEnddate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereActive($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereRemembersableId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereRemembersableType($value) 
 * @method static \Reminder dateIs($start, $end) 
 */
class Reminder extends Eloquent
{

    protected $table = 'reminders';


    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * A polymorphic thing or something!
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function remindersable()
    {
        return $this->morphTo();
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }
    public function scopeDateIs($query, Carbon $start, Carbon $end)
    {
        return $query->where('startdate', $start->format('Y-m-d'))->where('enddate', $end->format('Y-m-d'));
    }

    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }


} 