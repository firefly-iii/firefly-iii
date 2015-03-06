<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reminder
 *
 * @package FireflyIII\Models
 */
class Reminder extends Model
{


    protected $fillable = ['user_id', 'startdate', 'enddate', 'active', 'notnow', 'remindersable_id', 'remindersable_type',];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function remindersable()
    {
        return $this->morphTo();
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeOnDates(EloquentBuilder $query, Carbon $start, Carbon $end)
    {
        return $query->where('reminders.startdate', '=', $start->format('Y-m-d 00:00:00'))->where('reminders.enddate', '=', $end->format('Y-m-d 00:00:00'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
