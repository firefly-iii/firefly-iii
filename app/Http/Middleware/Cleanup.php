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
            return response('Unauthorized.', 401);
        }
        $count = -1;

        bcscale(0);

        if (env('RUNCLEANUP') == 'true') {
            $count = 0;
            $count = bcadd($count, $this->encryptAccountAndBills());
            $count = bcadd($count, $this->encryptBudgetsAndCategories());
            $count = bcadd($count, $this->encryptPiggiesAndJournals());
            $count = bcadd($count, $this->encryptRemindersAndPreferences());

        }
        if ($count == 0) {
            Session::flash('warning', 'Please open the .env file and change RUNCLEANUP=true to RUNCLEANUP=false');
        }

        return $next($request);

    }

    /**
     * @return int
     */
    protected function encryptAccountAndBills()
    {
        $count = 0;
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


        return $count;

    }

    /**
     * @return int
     */
    protected function encryptBudgetsAndCategories()
    {
        $count = 0;
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

        return $count;
    }

    /**
     * @return int
     */
    protected function encryptPiggiesAndJournals()
    {
        $count = 0;
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

        return $count;
    }

    /**
     * @return int
     */
    protected function encryptRemindersAndPreferences()
    {
        $count = 0;
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

        //encrypt preference name
        $set = Preference::whereNull('name_encrypted')->take(5)->get();
        /** @var Preference $entry */
        foreach ($set as $entry) {
            $count++;
            $name        = $entry->name;
            $entry->name = $name;
            $entry->save();
        }
        unset($set, $entry, $name);

        //encrypt preference data
        $set = Preference::whereNull('data_encrypted')->take(5)->get();
        /** @var Preference $entry */
        foreach ($set as $entry) {
            $count++;
            $data        = $entry->data;
            $entry->data = $data;
            $entry->save();
        }
        unset($set, $entry, $data);

        return $count;
    }

}
