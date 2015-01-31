<?php
namespace FireflyIII\Database\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;

/**
 * Class PiggyBank
 *
 * @package FireflyIII\Database
 */
class PiggyBank extends PiggyBankShared implements CUDInterface, CommonDatabaseCallsInterface, PiggyBankInterface
{
    /**
     * @param \PiggyBank $piggyBank
     * @param Carbon     $date
     *
     * @return mixed
     * @throws FireflyException
     */
    public function findRepetitionByDate(\PiggyBank $piggyBank, Carbon $date)
    {
        /** @var Collection $reps */
        $reps = $piggyBank->piggyBankRepetitions()->get();
        if ($reps->count() == 1) {
            return $reps->first();
        }
        // should filter the one we need:
        $repetitions = $reps->filter(
            function (\PiggyBankRepetition $rep) use ($date) {
                if ($date->between($rep->startdate, $rep->targetdate)) {
                    return $rep;
                }

                return null;
            }
        );
        if ($repetitions->count() == 0) {
            return null;
        }

        return $repetitions->first();
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return $this->getUser()->piggyBanks()->where('repeats', 0)->orderBy('name')->get();
    }
}
