<?php
use Carbon\Carbon;

/**
 * Class HomeController
 *
 */
class HomeController extends BaseController
{
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function flush()
    {
        Cache::flush();

        return Redirect::route('index');
    }

    public function repair() {
        Component::where('class', 'Budget')->get()->each(
            function (Component $c) {
                $entry  = [
                    'user_id' => $c->user_id,
                    'name'    => $c->name

                ];
                $budget = Budget::firstOrCreate($entry);
                Log::debug('Migrated budget #' . $budget->id . ': ' . $budget->name);
                // create entry in budget_transaction_journal
                $connections = DB::table('component_transaction_journal')->where('component_id', $c->id)->get();
                foreach ($connections as $connection) {
                    DB::table('budget_transaction_journal')->insert(
                        [
                            'budget_id'              => $budget->id,
                            'transaction_journal_id' => $connection->transaction_journal_id
                        ]
                    );
                }
            }
        );

        Component::where('class', 'Category')->get()->each(
            function (Component $c) {
                $entry    = [
                    'user_id' => $c->user_id,
                    'name'    => $c->name

                ];
                $category = Category::firstOrCreate($entry);
                Log::debug('Migrated category #' . $category->id . ': ' . $category->name);
                // create entry in category_transaction_journal
                $connections = DB::table('component_transaction_journal')->where('component_id', $c->id)->get();
                foreach ($connections as $connection) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $category->id,
                            'transaction_journal_id' => $connection->transaction_journal_id
                        ]
                    );
                }
            }
        );
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        // count, maybe Firefly needs some introducing text to show:
        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $jrnls */
        $jrnls = App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        /** @var \FireflyIII\Shared\Preferences\PreferencesInterface $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\PreferencesInterface');

        $count = $acct->countAssetAccounts();

        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());


        // get the preference for the home accounts to show:
        $frontpage = $preferences->get('frontpageAccounts', []);
        if ($frontpage->data == []) {
            $accounts = $acct->getAssetAccounts();
        } else {
            $accounts = $acct->getByIds($frontpage->data);
        }

        $transactions = [];
        foreach ($accounts as $account) {
            $set = $jrnls->getInDateRangeAccount($account, $start, $end, 10);
            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        // build the home screen:
        return View::make('index')->with('count', $count)->with('transactions', $transactions)->with('title', 'Firefly')->with('subTitle', 'What\'s playing?')
                   ->with('mainTitleIcon', 'fa-fire');
    }

    /**
     * @param $range
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rangeJump($range)
    {

        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y',];

        /** @var \FireflyIII\Shared\Preferences\PreferencesInterface $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\PreferencesInterface');

        if (in_array($range, $valid)) {
            $preferences->set('viewRange', $range);
            Session::forget('range');
        }

        return Redirect::back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionNext()
    {
        Navigation::next();

        return Redirect::back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionPrev()
    {
        Navigation::prev();

        return Redirect::back();
    }
}