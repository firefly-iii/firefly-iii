<?php

namespace FireflyIII\Http\Middleware;


use Carbon\Carbon;
use Closure;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Navigation;
use Session;


/**
 * Class PiggyBanks
 *
 * @package FireflyIII\Http\Middleware
 */
class PiggyBanks
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard $auth
     *
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->auth->check() && !$request->isXmlHttpRequest()) {
            // get piggy banks without a repetition:
            /** @var Collection $set */
            $set = $this->auth->user()->piggybanks()
                              ->leftJoin('piggy_bank_repetitions', 'piggy_banks.id', '=', 'piggy_bank_repetitions.piggy_bank_id')
                              ->where('piggy_banks.repeats', 0)
                              ->whereNull('piggy_bank_repetitions.id')
                              ->get(['piggy_banks.id', 'piggy_banks.startdate', 'piggy_banks.targetdate']);
            if ($set->count() > 0) {
                /** @var PiggyBank $partialPiggy */
                foreach ($set as $partialPiggy) {
                    $repetition = new PiggyBankRepetition;
                    $repetition->piggyBank()->associate($partialPiggy);
                    $repetition->startdate     = is_null($partialPiggy->startdate) ? null : $partialPiggy->startdate;
                    $repetition->targetdate    = is_null($partialPiggy->targetdate) ? null : $partialPiggy->targetdate;
                    $repetition->currentamount = 0;
                    $repetition->save();
                }
            }
            unset($partialPiggy, $set, $repetition);

            // get repeating piggy banks without a repetition for current time frame.
            /** @var Collection $set */
            $set = $this->auth->user()->piggybanks()->leftJoin(
                'piggy_bank_repetitions', function (JoinClause $join) {
                $join->on('piggy_bank_repetitions.piggy_bank_id', '=', 'piggy_banks.id')
                     ->where('piggy_bank_repetitions.targetdate', '>=', Session::get('start')->format('Y-m-d'))
                     ->where('piggy_bank_repetitions.startdate', '<=', Session::get('end')->format('Y-m-d'));
            }
            )
                              ->where('repeats', 1)
                              ->whereNull('piggy_bank_repetitions.id')
                              ->get(['piggy_banks.*']);

            // these piggy banks are missing a repetition. start looping and create them!
            if ($set->count() > 0) {
                /** @var PiggyBank $piggyBank */
                foreach ($set as $piggyBank) {
                    $start = clone $piggyBank->startdate;
                    $end   = clone $piggyBank->targetdate;
                    $max   = clone $piggyBank->targetdate;

                    // first loop: start date to target date.
                    // then, continue looping until end is > today
                    while ($start <= $max) {
                        // first loop fixes this date. or should fix it.
                        $max = new Carbon;

                        echo '[#'.$piggyBank->id.', from: '.$start->format('Y-m-d.').' to '.$end->format('Y-m-d.').']';
                        // create stuff. Or at least, try:
                        $repetition = $piggyBank->piggyBankRepetitions()->onDates($start, $end)->first();
                        if(!$repetition) {
                            $repetition = new PiggyBankRepetition;
                            $repetition->piggyBank()->associate($piggyBank);
                            $repetition->startdate     = $start;
                            $repetition->targetdate    = $end;
                            $repetition->currentamount = 0;
                            // it might exist, catch:
                            $repetition->save();
                        }

                        // start where end 'ended':
                        $start = clone $end;
                        // move end.
                        $end = Navigation::addPeriod($end, $piggyBank->rep_length, 0);

                    }


                    // first repetition: from original start to original target.
                    $repetition = new PiggyBankRepetition;
                    $repetition->piggyBank()->associate($piggyBank);
                    $repetition->startdate     = is_null($piggyBank->startdate) ? null : $piggyBank->startdate;
                    $repetition->targetdate    = is_null($piggyBank->targetdate) ? null : $piggyBank->targetdate;
                    $repetition->currentamount = 0;
                    // it might exist, catch:

                    // then, loop from original target up to now.
                }
            }


        }

        return $next($request);
    }
}