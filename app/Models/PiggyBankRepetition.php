<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PiggyBankRepetition
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $piggy_bank_id
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $targetdate
 * @property float $currentamount
 * @property string $currentamount_encrypted
 * @property-read \FireflyIII\Models\PiggyBank $piggyBank
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition wherePiggyBankId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereTargetdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereCurrentamount($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBankRepetition whereCurrentamountEncrypted($value)
 * @method static \FireflyIII\Models\PiggyBankRepetition onDates($start, $target)
 * @method static \FireflyIII\Models\PiggyBankRepetition relevantOnDate($date)
 */
class PiggyBankRepetition extends Model
{

    protected $fillable = ['piggy_bank_id', 'startdate', 'targetdate', 'currentamount'];
    protected $hidden   = ['currentamount_encrypted'];

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
