<?php

namespace FireflyIII\Http\Middleware;


use App;
use Closure;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
        }

        return $next($request);
    }
}
