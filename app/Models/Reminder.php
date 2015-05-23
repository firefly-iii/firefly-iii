<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reminder
 *
 * @codeCoverageIgnore
 *
 * @package FireflyIII\Models
 */
class Reminder extends Model
{


    protected $fillable = ['user_id', 'startdate', 'metadata', 'enddate', 'active', 'notnow', 'remindersable_id', 'remindersable_type',];
    protected $hidden   = ['encrypted'];

    /**
     *
     * @param $value
     *
     * @return int
     */
    public function getActiveAttribute($value)
    {
        return intval($value) == 1;
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     *
     * @param $value
     *
     * @return mixed
     */
    public function getMetadataAttribute($value)
    {
        if (intval($this->encrypted) == 1) {
            return json_decode(Crypt::decrypt($value));
        }

        return json_decode($value);
    }

    /**
     *
     * @param $value
     *
     * @return bool
     */
    public function getNotnowAttribute($value)
    {
        return intval($value) == 1;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function remindersable()
    {
        return $this->morphTo();
    }

    /**
     *
     * @param EloquentBuilder $query
     * @param Carbon          $start
     * @param Carbon          $end
     *
     * @return $this
     */
    public function scopeOnDates(EloquentBuilder $query, Carbon $start, Carbon $end)
    {
        return $query->where('reminders.startdate', '=', $start->format('Y-m-d 00:00:00'))->where('reminders.enddate', '=', $end->format('Y-m-d 00:00:00'));
    }

    /**
     *
     * @param EloquentBuilder $query
     *
     * @return $this
     */
    public function scopeToday(EloquentBuilder $query)
    {
        $today = new Carbon;

        return $query->where('startdate', '<=', $today->format('Y-m-d 00:00:00'))->where('enddate', '>=', $today->format('Y-m-d 00:00:00'))->where('active', 1)
                     ->where('notnow', 0);
    }

    /**
     *
     * @param $value
     */
    public function setMetadataAttribute($value)
    {
        $this->attributes['encrypted'] = true;
        $this->attributes['metadata']  = Crypt::encrypt(json_encode($value));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
