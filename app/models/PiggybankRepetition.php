<?php
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Watson\Validating\ValidatingTrait;

/**
 * Class PiggybankRepetition
 */
class PiggybankRepetition extends Eloquent
{
    use ValidatingTrait;
    public static $rules
        = [
            'piggybank_id'  => 'required|exists:piggybanks,id',
            'targetdate'    => 'date',
            'startdate'     => 'date',
            'currentamount' => 'required|numeric'];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'targetdate', 'startdate'];
    }

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @return float|int
     */
    public function pct()
    {
        $total = $this->piggybank->targetamount;
        $saved = $this->currentamount;
        if ($total == 0) {
            return 0;
        }
        $pct = round(($saved / $total) * 100, 1);

        return $pct;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }

    /**
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeStarts(Builder $query, Carbon $date)
    {
        $query->where('startdate', $date->format('Y-m-d'));
    }

    /**
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeTargets(Builder $query, Carbon $date)
    {
        $query->where('targetdate', $date->format('Y-m-d'));
    }


} 