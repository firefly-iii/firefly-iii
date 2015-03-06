<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 06/03/15
 * Time: 15:04
 */

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Reminder;
use Illuminate\Contracts\Auth\Guard;
use Log;
use Navigation;

/**
 * Class Reminders
 *
 * @package FireflyIII\Http\Middleware
 */
class Reminders
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
    public function handle($request, Closure $next)
    {
        if ($this->auth->check()) {
            // do reminders stuff.
            $piggyBanks = $this->auth->user()->piggyBanks()->where('remind_me', 1)->get();
            $today      = new Carbon;
            /** @var \FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface $repository */
            $repository = App::make('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');

            /** @var PiggyBank $piggyBank */
            foreach ($piggyBanks as $piggyBank) {
                $startDate  = is_null($piggyBank->startdate) ? 'null' : $piggyBank->startdate->format('d M Y');
                $targetDate = is_null($piggyBank->targetdate) ? 'null' : $piggyBank->targetdate->format('d M Y');
                Log::debug('PiggyBank: #' . $piggyBank->id . ', name: ' . $piggyBank->name);
                Log::debug('Startdate: ' . $startDate . ', target date: ' . $targetDate);

                if (!is_null($piggyBank->targetdate)) {
                    // count back until now.
                    //                    echo 'Count back!<br>';
                    $start = $piggyBank->targetdate;
                    $end   = $piggyBank->startdate;

                    while ($start >= $end) {
                        $currentEnd   = clone $start;
                        $start        = Navigation::subtractPeriod($start, $piggyBank->reminder, 1);
                        $currentStart = clone $start;
                        Log::debug('Now range: [' . $currentStart->format('d M Y') . '] to [' . $currentEnd->format('d M Y') . ']');

                        // for today?
                        if ($today < $currentEnd && $today > $currentStart) {
                            Log::debug('Today!');

                            // find a reminder first?
                            $reminders = $this->auth->user()->reminders()
                                                    ->where('remindersable_id', $piggyBank->id)
                                                    ->onDates($currentStart, $currentEnd)
                                                    ->count();
                            Log::debug('Found ' . $reminders . ' reminders');

                            if ($reminders == 0) {
                                // create a reminder here!
                                Log::debug('create reminder!');
                                $repository->createReminder($piggyBank, $currentStart, $currentEnd);
                            }
                            // stop looping, we're done.
                            break;
                        }
                    }
                } else {
                    $start = clone $piggyBank->startdate;
                    while ($start < $today) {
                        $currentStart = clone $start;
                        $start        = Navigation::addPeriod($start, $piggyBank->reminder, 0);
                        $currentEnd   = clone $start;
                        Log::debug('Now range: [' . $currentStart->format('d M Y') . '] to [' . $currentEnd->format('d M Y') . ']');

                        // for today?
                        if ($today < $currentEnd && $today > $currentStart) {
                            $reminders = $this->auth->user()->reminders()
                                                    ->where('remindersable_id', $piggyBank->id)
                                                    ->onDates($currentStart, $currentEnd)
                                                    ->count();
                            Log::debug('Found ' . $reminders . ' reminders');

                            if ($reminders == 0) {
                                // create a reminder here!
                                Log::debug('create reminder!');
                                $repository->createReminder($piggyBank, $currentStart, $currentEnd);

                            }
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}