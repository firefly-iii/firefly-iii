<?php

use Carbon\Carbon;
use Firefly\Database\SingleTableInheritanceEntity;


/**
 * Class Reminder
 *     // reminder for: recurring, piggybank.
 */
class Reminder extends SingleTableInheritanceEntity
{

    protected $table = 'reminders';
    protected $subclassField = 'class';


    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }

    public function render() {
      return '';



    }

    public function scopeValidOn($query, Carbon $date)
    {
        return $query->where('startdate', '<=', $date->format('Y-m-d'))->where('enddate', '>=', $date->format('Y-m-d'))
            ->where('active', 1);
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