<?php

/**
 * RecurringTransactionReminder
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
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereClass($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder wherePiggybankId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereRecurringTransactionId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereEnddate($value)
 * @method static \Illuminate\Database\Query\Builder|\RecurringTransactionReminder whereActive($value)
 * @method static \Reminder validOn($date)
 * @method static \Reminder validOnOrAfter($date)
 */
class RecurringTransactionReminder extends Reminder
{
    protected $isSubclass = true;

    public function render()
    {
        return '123';
    }


}