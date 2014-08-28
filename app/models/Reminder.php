<?php

use Carbon\Carbon;
use Firefly\Database\SingleTableInheritanceEntity;


/**
 * Class Reminder
 *     // reminder for: recurring, piggybank.
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $class
 * @property integer $piggybank_id
 * @property integer $recurring_transaction_id
 * @property integer $user_id
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $enddate
 * @property boolean $active
 * @property-read \Piggybank $piggybank
 * @property-read \RecurringTransaction $recurringTransaction
 * @property-read \User $user
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereClass($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder wherePiggybankId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereRecurringTransactionId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereUserId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereStartdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereEnddate($value) 
 * @method static \Illuminate\Database\Query\Builder|\Reminder whereActive($value) 
 * @method static \Reminder validOn($date) 
 * @method static \Reminder validOnOrAfter($date) 
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

    public function recurringTransaction()
    {
        return $this->belongsTo('RecurringTransaction');
    }

    public function render()
    {
        return '';


    }

    public function scopeValidOn($query, Carbon $date)
    {
        return $query->where('startdate', '<=', $date->format('Y-m-d'))->where('enddate', '>=', $date->format('Y-m-d'))
            ->where('active', 1);
    }

    public function scopeValidOnOrAfter($query, Carbon $date)
    {
        return $query->where(
            function ($q) use ($date) {
                $q->where('startdate', '<=', $date->format('Y-m-d'))->where(
                    'enddate', '>=', $date->format('Y-m-d')
                );
                $q->orWhere(
                    function ($q) use ($date) {
                        $q->where('startdate', '>=', $date);
                        $q->where('enddate', '>=', $date);
                    }
                );
            }
        )->where('active', 1);
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