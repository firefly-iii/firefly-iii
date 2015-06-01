<?php

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Reminder;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use View;

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
    public function handle(Request $request, Closure $next)
    {
        if ($this->auth->check() && !$request->isXmlHttpRequest()) {
            // do reminders stuff.
            $reminders = [];
            if ($this->auth->user() instanceof User) {
                $piggyBanks = $this->auth->user()->piggyBanks()->where('remind_me', 1)->get();
                /** @var \FireflyIII\Helpers\Reminders\ReminderHelperInterface $helper */
                $helper = App::make('FireflyIII\Helpers\Reminders\ReminderHelperInterface');

                /** @var PiggyBank $piggyBank */
                foreach ($piggyBanks as $piggyBank) {
                    $helper->createReminders($piggyBank, new Carbon);
                }
                // delete invalid reminders
                // this is a construction SQLITE cannot handle :(
                if (env('DB_CONNECTION') != 'sqlite') {
                    Reminder::whereUserId($this->auth->user()->id)
                            ->leftJoin('piggy_banks', 'piggy_banks.id', '=', 'remindersable_id')
                            ->whereNull('piggy_banks.id')
                            ->delete();
                }

                // get and list active reminders:
                $reminders = $this->auth->user()->reminders()->today()->get();
                $reminders->each(
                    function (Reminder $reminder) use ($helper) {
                        $reminder->description = $helper->getReminderText($reminder);
                    }
                );
            }
            View::share('reminders', $reminders);
        }

        return $next($request);
    }
}
