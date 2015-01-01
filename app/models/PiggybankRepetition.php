<?php
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class PiggyBankRepetition
 */
class PiggyBankRepetition extends Eloquent
{
    use ValidatingTrait;
    public static $rules
        = [
            'piggy_bank_id'  => 'required|exists:piggy_banks,id',
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggyBank()
    {
        return $this->belongsTo('PiggyBank');
    }

    /**
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeStarts(Builder $query, Carbon $date)
    {
        $query->where('startdate', $date->format('Y-m-d 00:00:00'));
    }

    /**
     * @param Builder $query
     * @param Carbon  $date
     */
    public function scopeTargets(Builder $query, Carbon $date)
    {
        $query->where('targetdate', $date->format('Y-m-d 00:00:00'));
    }


} 