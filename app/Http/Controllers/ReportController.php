<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Redirect;
use Session;
use View;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportController extends Controller
{

    /** @var ReportHelperInterface */
    protected $helper;

    /**
     * @codeCoverageIgnore
     *
     * @param ReportHelperInterface $helper
     */
    public function __construct(ReportHelperInterface $helper)
    {
        parent::__construct();
        $this->helper = $helper;

        View::share('title', trans('firefly.reports'));
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return View
     * @internal param ReportHelperInterface $helper
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $start  = Session::get('first');
        $months = $this->helper->listOfMonths($start);

        // does the user have shared accounts?
        $accounts  = $repository->getAccounts(['Default account', 'Asset account']);
        $hasShared = false;

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->getMeta('accountRole') == 'sharedAsset') {
                $hasShared = true;
            }
        }


        return view('reports.index', compact('months', 'accounts', 'hasShared', 'start'));
    }

    /**
     * TODO needs a custom validator for ease of use.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function select(AccountRepositoryInterface $repository)
    {
        // process post data, give error, otherwise send redirect.
        $report = Input::get('report_type');
        $parts  = [$report];

        // date
        $ranges = explode(' - ', Input::get('daterange'));
        $start  = clone Session::get('start');
        $end    = clone Session::get('end');

        // kind of primitive but OK for now.
        if (count($ranges) == 2 && strlen($ranges[0]) == 10 && strlen($ranges[1]) == 10) {
            $start = new Carbon($ranges[0]);
            $end   = new Carbon($ranges[1]);
        }
        if ($end <= $start) {
            Session::flash('error', 'Messed up the date!');

            return Redirect::route('reports.index');
        }
        $parts[] = $start->format('Ymd');
        $parts[] = $end->format('Ymd');

        if (is_array(Input::get('accounts'))) {
            foreach (Input::get('accounts') as $accountId) {
                $account = $repository->find($accountId);
                if ($account) {
                    $parts[] = $account->id;
                }
            }
        }
        if (count($parts) == 3) {
            Session::flash('error', 'Select some accounts!');

            return Redirect::route('reports.index');
        }


        $url = join(';', $parts);

        return Redirect::route('reports.report', [$url]);

    }

    /**
     * @param string $year
     * @param string $month
     *
     * @param bool   $shared
     *
     * @return \Illuminate\View\View
     */
    public function month($year = '2014', $month = '1', $shared = false)
    {
        $start            = new Carbon($year . '-' . $month . '-01');
        $subTitle         = trans('firefly.reportForMonth', ['month' => $start->formatLocalized($this->monthFormat)]);
        $subTitleIcon     = 'fa-calendar';
        $end              = clone $start;
        $incomeTopLength  = 8;
        $expenseTopLength = 8;
        if ($shared == 'shared') {
            $shared   = true;
            $subTitle = trans('firefly.reportForMonthShared', ['month' => $start->formatLocalized($this->monthFormat)]);
        }

        $end->endOfMonth();

        $accounts   = $this->helper->getAccountReport($start, $end, $shared);
        $incomes    = $this->helper->getIncomeReport($start, $end, $shared);
        $expenses   = $this->helper->getExpenseReport($start, $end, $shared);
        $budgets    = $this->helper->getBudgetReport($start, $end, $shared);
        $categories = $this->helper->getCategoryReport($start, $end, $shared);
        $balance    = $this->helper->getBalanceReport($start, $end, $shared);
        $bills      = $this->helper->getBillReport($start, $end);

        Session::flash('gaEventCategory', 'report');
        Session::flash('gaEventAction', 'month');
        Session::flash('gaEventLabel', $start->format('F Y'));


        return view(
            'reports.month',
            compact(
                'start', 'shared',
                'subTitle', 'subTitleIcon',
                'accounts',
                'incomes', 'incomeTopLength',
                'expenses', 'expenseTopLength',
                'budgets', 'balance',
                'categories',
                'bills'
            )
        );

    }

    /**
     * @param      $year
     *
     * @param bool $shared
     *
     * @return $this
     */
    public function year($year, $shared = false)
    {
        $start            = new Carbon('01-01-' . $year);
        $end              = clone $start;
        $subTitle         = trans('firefly.reportForYear', ['year' => $year]);
        $subTitleIcon     = 'fa-bar-chart';
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        if ($shared == 'shared') {
            $shared   = true;
            $subTitle = trans('firefly.reportForYearShared', ['year' => $year]);
        }
        $end->endOfYear();

        $accounts = $this->helper->getAccountReport($start, $end, $shared);
        $incomes  = $this->helper->getIncomeReport($start, $end, $shared);
        $expenses = $this->helper->getExpenseReport($start, $end, $shared);

        Session::flash('gaEventCategory', 'report');
        Session::flash('gaEventAction', 'year');
        Session::flash('gaEventLabel', $start->format('Y'));

        return view(
            'reports.year',
            compact('start', 'shared', 'accounts', 'incomes', 'expenses', 'subTitle', 'subTitleIcon', 'incomeTopLength', 'expenseTopLength')
        );
    }

    /**
     * @param            $report_type
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    public function report($report_type, Carbon $start, Carbon $end, Collection $accounts)
    {
        // some fields for translation:
        $subTitle         = trans('firefly.reportForMonth', ['month' => $start->formatLocalized($this->monthFormat)]);
        $subTitleIcon     = 'fa-calendar';
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        // get report stuff!
        $accountReport = $this->helper->getAccountReportForList($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReportForList($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReportForList($start, $end, $accounts);
        $budgets       = $this->helper->getBudgetReportForList($start, $end, $accounts);
        $categories    = $this->helper->getCategoryReportForList($start, $end, $accounts);
        $balance       = $this->helper->getBalanceReportForList($start, $end, $accounts);
        $bills         = $this->helper->getBillReportForList($start, $end, $accounts);

        // and some id's, joined:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountIds = join(';', $accountIds);

        // continue!
        return view(
            'reports.default',
            compact(
                'start', 'end', 'report_type',
                'subTitle', 'subTitleIcon',
                'accountReport',
                'incomes', 'incomeTopLength',
                'expenses', 'expenseTopLength',
                'budgets', 'balance',
                'categories',
                'bills',
                'accountIds', 'report_type'
            )
        );

    }


}
