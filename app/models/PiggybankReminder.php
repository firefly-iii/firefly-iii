<?php
use Carbon\Carbon;

/**
 * PiggybankReminder
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
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereClass($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder wherePiggybankId($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereRecurringTransactionId($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereEnddate($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankReminder whereActive($value)
 * @method static \Reminder validOn($date)
 * @method static \Reminder validOnOrAfter($date)
 */
class PiggybankReminder extends Reminder
{
    protected $isSubclass = true;

    /**
     * @return string
     * @throws Firefly\Exception\FireflyException
     */
    public function render()
    {
        /** @var \Piggybank $piggyBank */
        $piggyBank = $this->piggybank;


        $fullText
            = 'In order to save enough money for <a href="' . route('piggybanks.show', $piggyBank->id) . '">"' . e(
                $piggyBank->name
            ) . '"</a> you';

        $fullText .= ' should save at least ' . mf($this->amountToSave(), false) . ' this ' . $piggyBank->reminder
            . ', before ' . $this->enddate->format('M jS, Y');

        return $fullText;
    }


    /**
     * @return float
     * @throws Firefly\Exception\FireflyException
     */
    public function amountToSave()
    {
        /** @var \Piggybank $piggyBank */
        $piggyBank = $this->piggybank;
        /** @var \PiggybankRepetition $repetition */
        $repetition = $piggyBank->currentRelevantRep();

        // if the target date of the repetition is zero, we use the created_at date of the repetition
        // and add two years; it's the same routine used elsewhere.
        if (is_null($repetition->targetdate)) {
            $targetdate = clone $repetition->created_at;
            $targetdate->addYears(2);
        } else {
            $targetdate = $repetition->targetdate;
        }


        $today = new Carbon;
        $diff = $today->diff($targetdate);
        $left = $piggyBank->targetamount - $repetition->currentamount;
        // to prevent devide by zero:
        $piggyBank->reminder_skip = $piggyBank->reminder_skip < 1 ? 1 : $piggyBank->reminder_skip;
        $toSave = 0;
        switch ($piggyBank->reminder) {
            case 'day':
                throw new \Firefly\Exception\FireflyException('No impl day reminder/ PiggyBankReminder Render');
                break;
            case 'week':
                $weeks = ceil($diff->days / 7);
                $toSave = $left / ($weeks / $piggyBank->reminder_skip);
                break;
            case 'month':
                $toSave = $left / ($diff->m / $piggyBank->reminder_skip);
                break;
            case 'year':
                throw new \Firefly\Exception\FireflyException('No impl year reminder/ PiggyBankReminder Render');
                break;
        }

        return floatval($toSave);
    }

} 