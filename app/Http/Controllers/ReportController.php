<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use Illuminate\Support\Collection;
use Session;
use View;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportController extends Controller
{


    protected $accountHelper;
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

        $this->helper        = $helper;
        $this->accountHelper = app('FireflyIII\Helpers\Report\AccountReportHelperInterface');

        View::share('title', trans('firefly.reports'));
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @param            $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    public function defaultMonth($reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        // get report stuff!
        $accountReport = $this->accountHelper->getAccountReport($start, $end, $accounts); // done (+2)
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts); // done (+3)
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts); // done (+1)
        $budgets       = $this->helper->getBudgetReport($start, $end, $accounts); // done (+5)
        $categories    = $this->helper->getCategoryReport($start, $end, $accounts); // done (+1) (20)
        $balance       = $this->helper->getBalanceReport($start, $end, $accounts); // +566
        $bills         = $this->helper->getBillReport($start, $end, $accounts);

        // and some id's, joined:
        $accountIds = join(',', $accounts->pluck('id')->toArray());

        // continue!
        return view(
            'reports.default.month',
            compact(
                'start', 'end', 'reportType',
                'accountReport',
                'incomes', 'incomeTopLength',
                'expenses', 'expenseTopLength',
                'budgets', 'balance',
                'categories',
                'bills',
                'accountIds', 'reportType'
            )
        );
    }

    /**
     * @param $reportType
     * @param $start
     * @param $end
     * @param $accounts
     *
     * @return View
     */
    public function defaultMultiYear($reportType, $start, $end, $accounts)
    {

        $incomeTopLength  = 8;
        $expenseTopLength = 8;
        // list of users stuff:
        $budgets       = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface')->getActiveBudgets();
        $categories    = app('FireflyIII\Repositories\Category\CategoryRepositoryInterface')->listCategories();
        $accountReport = $this->accountHelper->getAccountReport($start, $end, $accounts); // done (+2)
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts); // done (+3)
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts); // done (+1)

        // and some id's, joined:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountIds = join(',', $accountIds);

        return view(
            'reports.default.multi-year',
            compact(
                'budgets', 'accounts', 'categories', 'start', 'end', 'accountIds', 'reportType', 'accountReport', 'incomes', 'expenses',
                'incomeTopLength', 'expenseTopLength'
            )
        );
    }

    /**
     * @param            $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    public function defaultYear($reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        $accountReport = $this->accountHelper->getAccountReport($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts);

        Session::flash('gaEventCategory', 'report');
        Session::flash('gaEventAction', 'year');
        Session::flash('gaEventLabel', $start->format('Y'));

        // and some id's, joined:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountIds = join(',', $accountIds);

        return view(
            'reports.default.year',
            compact(
                'start', 'accountReport', 'incomes', 'reportType', 'accountIds', 'end',
                'expenses', 'incomeTopLength', 'expenseTopLength'
            )
        );
    }

    /**
     * @param ARI $repository
     *
     * @return View
     * @internal param ReportHelperInterface $helper
     */
    public function index(ARI $repository)
    {
        $start  = Session::get('first');
        $months = $this->helper->listOfMonths($start);

        // does the user have shared accounts?
        $accounts = $repository->getAccounts(['Default account', 'Asset account']);
        // get id's for quick links:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds [] = $account->id;
        }
        $accountList = join(',', $accountIds);


        return view(
            'reports.index', compact(
                               'months', 'accounts', 'start', 'accountList'
                           )
        );
    }

    /**
     * @param            $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    public function report($reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        // throw an error if necessary.
        if ($end < $start) {
            return view('error')->with('message', 'End date cannot be before start date, silly!');
        }

        // lower threshold
        if ($start < Session::get('first')) {
            $start = Session::get('first');
        }

        switch ($reportType) {
            default:
            case 'default':

                View::share(
                    'subTitle', trans(
                                  'firefly.report_default',
                                  [
                                      'start' => $start->formatLocalized($this->monthFormat),
                                      'end'   => $end->formatLocalized($this->monthFormat),
                                  ]
                              )
                );
                View::share('subTitleIcon', 'fa-calendar');

                // more than one year date difference means year report.
                if ($start->diffInMonths($end) > 12) {
                    return $this->defaultMultiYear($reportType, $start, $end, $accounts);
                }
                // more than two months date difference means year report.
                if ($start->diffInMonths($end) > 1) {
                    return $this->defaultYear($reportType, $start, $end, $accounts);
                }

                return $this->defaultMonth($reportType, $start, $end, $accounts);
        }


    }


}
