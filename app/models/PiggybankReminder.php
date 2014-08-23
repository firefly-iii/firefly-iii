<?php
use Carbon\Carbon;

/**
 * Class PiggybankReminder
 */
class PiggybankReminder extends Reminder
{
    protected $isSubclass = true;


    public function amountToSave()
    {
        /** @var \Piggybank $piggyBank */
        $piggyBank = $this->piggybank;
        /** @var \PiggybankRepetition $repetition */
        $repetition = $piggyBank->currentRelevantRep();

        $today = new Carbon;
        $diff = $today->diff($repetition->targetdate);
        $left = $piggyBank->targetamount - $repetition->currentamount;
        // to prevent devide by zero:
        $piggyBank->reminder_skip = $piggyBank->reminder_skip < 1 ? 1 : $piggyBank->reminder_skip;
        $toSave = 0;
        switch ($piggyBank->reminder) {
            case 'day':
                throw new \Firefly\Exception\FireflyException('No impl day reminder/ PiggyBankReminder Render');
                break;
            case 'week':
                throw new \Firefly\Exception\FireflyException('No impl week reminder/ PiggyBankReminder Render');
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

} 