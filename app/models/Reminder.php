<?php

use Carbon\Carbon;
use Watson\Validating\ValidatingTrait;

class Reminder extends Eloquent
{
    use ValidatingTrait;

    protected $table = 'reminders';

    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

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

    public function scopeDateIs($query, Carbon $start, Carbon $end)
    {
        return $query->where('startdate', $start->format('Y-m-d'))->where('enddate', $end->format('Y-m-d'));
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
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


} 