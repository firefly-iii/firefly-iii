<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * FireflyIII\Models\PiggyBankRepetition
 *
 * @property integer        $id
 * @property Carbon         $created_at
 * @property Carbon         $updated_at
 * @property integer        $piggy_bank_id
 * @property Carbon         $startdate
 * @property Carbon         $targetdate
 * @property float          $currentamount
 * @property-read PiggyBank $piggyBank
 * @method static Builder|PiggyBankRepetition onDates($start, $target)
 * @method static Builder|PiggyBankRepetition relevantOnDate($date)
 */
class PiggyBankRepetition extends Model
{

    protected $fillable = ['piggy_bank_id', 'startdate', 'targetdate', 'currentamount'];
    protected $hidden   = ['currentamount_encrypted'];
    protected $dates    = ['created_at', 'updated_at', 'startdate', 'targetdate'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggyBank()
    {
        return $this->belongsTo('FireflyIII\Models\PiggyBank');
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $start
     * @param Carbon          $target
     *
     * @return $this
     */
    public function scopeOnDates(EloquentBuilder $query, Carbon $start, Carbon $target)
    {
        return $query->where('startdate', $start->format('Y-m-d'))->where('targetdate', $target->format('Y-m-d'));
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeRelevantOnDate(EloquentBuilder $query, Carbon $date)
    {
        return $query->where(
            function (EloquentBuilder $q) use ($date) {
                $q->where('startdate', '<=', $date->format('Y-m-d 00:00:00'));
                $q->orWhereNull('startdate');
            }
        )
                     ->where(
                         function (EloquentBuilder $q) use ($date) {

                             $q->where('targetdate', '>=', $date->format('Y-m-d 00:00:00'));
                             $q->orWhereNull('targetdate');
                         }
                     );
    }

    /**
     * @param $value
     */
    public function setCurrentamountAttribute($value)
    {
        $this->attributes['currentamount'] = strval(round($value, 2));
    }

}
