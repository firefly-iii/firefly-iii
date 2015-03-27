<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Carbon\Carbon;
/**
 * Class PiggyBankRepetition
 *
 * @package FireflyIII\Models
 */
class PiggyBankRepetition extends Model
{

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'targetdate'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggyBank()
    {
        return $this->belongsTo('FireflyIII\Models\PiggyBank');
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
            function($q) use ($date) {
                $q->where('startdate', '<=', $date->format('Y-m-d 00:00:00'));
                $q->orWhereNull('startdate');
            })

            ->where(function($q) use ($date) {

                $q->where('targetdate', '>=', $date->format('Y-m-d 00:00:00'));
                $q->orWhereNull('targetdate');
            });
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
        return $query->where('startdate',$start->format('Y-m-d'))->where('targetdate',$target->format('Y-m-d'));
    }

}
