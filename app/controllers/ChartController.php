<?php

use Firefly\Helper\Controllers\ChartInterface;
use Firefly\Storage\Account\AccountRepositoryInterface;

/**
 * Class ChartController
 */
class ChartController extends BaseController
{

    protected $_chart;
    protected $_accounts;


    /**
     * @param ChartInterface $chart
     */
    public function __construct(ChartInterface $chart, AccountRepositoryInterface $accounts)
    {
        $this->_chart = $chart;
        $this->_accounts = $accounts;
    }

    /**
     * @param Account $account
     *
     * @return mixed
     */
    public function homeAccount(Account $account = null)
    {
        // get preferences and accounts (if necessary):
        $data = [];
        $start = Session::get('start');
        $end = Session::get('end');

        if (is_null($account)) {
            // get, depending on preferences:
            /** @var  \Firefly\Helper\Preferences\PreferencesHelperInterface $prefs */
            $prefs = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
            $pref = $prefs->get('frontpageAccounts', []);

            /** @var \Firefly\Storage\Account\AccountRepositoryInterface $acct */
            $acct = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
            $accounts = $acct->getByIds($pref->data);
        } else {
            $accounts = [$account];
        }
        // loop and get array data.

        $url = count($accounts) == 1
            ? '<a href="' . route('accounts.show', [$account->id]) . '">View more</a>'
            :
            '<a href="' . route('accounts.index') . '">View more</a>';
        $data = [
            'chart_title' => count($accounts) == 1 ? $accounts[0]->name : 'All accounts',
            'subtitle'    => $url,
            'series'      => []
        ];

        foreach ($accounts as $account) {
            $data['series'][] = $this->_chart->account($account, $start, $end);
        }

        return Response::json($data);
    }

    /**
     * Return some beneficiary info for an account and a date.
     *
     * @param $name
     * @param $day
     * @param $month
     * @param $year
     *
     * @return $this|\Illuminate\View\View
     */
    public function homeAccountInfo($name, $day, $month, $year)
    {

        $account = $this->_accounts->findByName($name);
        $date = Carbon::createFromDate($year, $month, $day);
        if ($account) {
            $result = $this->_chart->accountDailySummary($account, $date);

            return View::make('charts.info')->with('rows', $result['rows'])->with('sum', $result['sum']);
        } else {
            return View::make('error')->with('message', 'No account!');
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function homeBudgets()
    {
        $start = \Session::get('start');

        return Response::json($this->_chart->budgets($start));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function homeCategories()
    {
        $start = Session::get('start');
        $end = Session::get('end');

        return Response::json($this->_chart->categories($start, $end));


    }
    public function categoryShowChart(Category $category) {
        $start = Session::get('start');
        $end = Session::get('end');
        $range = Session::get('range');

        $serie = $this->_chart->categoryShowChart($category, $range, $start, $end);
        $data = [
            'chart_title' => $category->name,
            'subtitle' => '<a href="' . route('categories.show', [$category->id]) . '">View more</a>',
            'series' => $serie
        ];
        return Response::json($data);



    }
}