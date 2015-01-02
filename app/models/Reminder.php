<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Watson\Validating\ValidatingTrait;

/**
 * Class Reminder
 */
class Reminder extends Eloquent
{
    use ValidatingTrait;

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
     * @param EloquentBuilder $query
     * @param Carbon          $start
     * @param Carbon          $end
     *
     * @return mixed
     */
    public function scopeDateIs(EloquentBuilder $query, Carbon $start, Carbon $end)
    {
        return $query->where('startdate', $start->format('Y-m-d 00:00:00'))->where('enddate', $end->format('Y-m-d 00:00:00'));
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
