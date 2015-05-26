<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reminder
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property integer $user_id 
 * @property \Carbon\Carbon $startdate 
 * @property \Carbon\Carbon $enddate 
 * @property boolean $active 
 * @property boolean $notnow 
 * @property integer $remindersable_id 
 * @property string $remindersable_type 
 * @property string $metadata 
 * @property boolean $encrypted 
 * @property-read \ $remindersable 
 * @property-read \FireflyIII\User $user 
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereEnddate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereNotnow($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereRemindersableId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereRemindersableType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereMetadata($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Reminder whereEncrypted($value)
 * @method static \FireflyIII\Models\Reminder onDates($start, $end)
 * @method static \FireflyIII\Models\Reminder today()
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
