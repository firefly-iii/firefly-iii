<?php namespace FireflyIII\Http\Middleware;

use Closure;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Reminder;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Session;

/**
 * Class Cleanup
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Middleware
 */
class Cleanup
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
        if ($this->auth->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('auth/login');
            }
        }
        $run   = env('RUNCLEANUP') == 'true' ? true : false;
        $count = 0;

        if ($run) {
            // encrypt account name
            $set = Account::where('encrypted', 0)->take(5)->get();
            /** @var Account $entry */
            foreach ($set as $entry) {
                $count++;
                $name        = $entry->name;
                $entry->name = $name;
                $entry->save();
            }
            unset($set, $entry, $name);

            // encrypt bill name
            $set = Bill::where('name_encrypted', 0)->take(5)->get();
            /** @var Bill $entry */
            foreach ($set as $entry) {
                $count++;
                $name        = $entry->name;
                $entry->name = $name;
                $entry->save();
            }
            unset($set, $entry, $name);

            // encrypt bill match
            $set = Bill::where('match_encrypted', 0)->take(5)->get();
            /** @var Bill $entry */
            foreach ($set as $entry) {
                $match        = $entry->match;
                $entry->match = $match;
                $entry->save();
            }
            unset($set, $entry, $match);

            // encrypt budget name
            $set = Budget::where('encrypted', 0)->take(5)->get();
            /** @var Budget $entry */
            foreach ($set as $entry) {
                $count++;
                $name        = $entry->name;
                $entry->name = $name;
                $entry->save();
            }
            unset($set, $entry, $name);

            // encrypt category name
            $set = Category::where('encrypted', 0)->take(5)->get();
            /** @var Category $entry */
            foreach ($set as $entry) {
                $count++;
                $name        = $entry->name;
                $entry->name = $name;
                $entry->save();
            }
            unset($set, $entry, $name);

            // encrypt piggy bank name
            $set = PiggyBank::where('encrypted', 0)->take(5)->get();
            /** @var PiggyBank $entry */
            foreach ($set as $entry) {
                $count++;
                $name        = $entry->name;
                $entry->name = $name;
                $entry->save();
            }
            unset($set, $entry, $name);

            // encrypt transaction journal description
            $set = TransactionJournal::where('encrypted', 0)->take(5)->get();
            /** @var TransactionJournal $entry */
            foreach ($set as $entry) {
                $count++;
                $description        = $entry->description;
                $entry->description = $description;
                $entry->save();
            }
            unset($set, $entry, $description);

            // encrypt reminder metadata
            $set = Reminder::where('encrypted', 0)->take(5)->get();
            /** @var Reminder $entry */
            foreach ($set as $entry) {
                $count++;
                $metadata        = $entry->metadata;
                $entry->metadata = $metadata;
                $entry->save();
            }
            unset($set, $entry, $metadata);

            // encrypt account virtual balance amount
            $set = Account::whereNull('virtual_balance_encrypted')->take(5)->get();
            /** @var Account $entry */
            foreach ($set as $entry) {
                $count++;
                $amount                 = $entry->amount;
                $entry->virtual_balance = $amount;
                $entry->save();
            }
            unset($set, $entry, $amount);

            // encrypt bill amount_min
            $set = Bill::whereNull('amount_min_encrypted')->take(5)->get();
            /** @var Bill $entry */
            foreach ($set as $entry) {
                $count++;
                $amount            = $entry->amount_min;
                $entry->amount_min = $amount;
                $entry->save();
            }
            unset($set, $entry, $amount);

            // encrypt bill amount_max
            $set = Bill::whereNull('amount_max_encrypted')->take(5)->get();
            /** @var Bill $entry */
            foreach ($set as $entry) {
                $count++;
                $amount            = $entry->amount_max;
                $entry->amount_max = $amount;
                $entry->save();
            }
            unset($set, $entry, $amount);

            //encrypt budget limit amount
            //encrypt limit repetition amount
            //encrypt piggy bank event amount
            //encrypt piggy bank repetition currentamount
            //encrypt piggy bank targetamount
            //encrypt preference name (add field)
            //encrypt preference data (add field)
            //encrypt transaction amount
        }
        if ($count == 0 && $run) {
            Session::flash('warning', 'Please open the .env file and change RUNCLEANUP=true to RUNCLEANUP=false');
        }


        //
        //
        //create get/set routine for budget limit amount
        //create get/set routine for limit repetition amount
        //create get/set routine for piggy bank event amount
        //create get/set routine for piggy bank repetition currentamount
        //create get/set routine for piggy bank targetamount
        //create get/set routine for transaction amount


        return $next($request);
    }

}
