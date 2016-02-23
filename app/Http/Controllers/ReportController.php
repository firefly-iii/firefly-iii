<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use Illuminate\Support\Collection;
use Log;
use Preferences;
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
    protected $balanceHelper;
    protected $budgetHelper;
    /** @var ReportHelperInterface */
    protected $helper;

    /**
     *
     *
     * @param ReportHelperInterface $helper
     */
    public function __construct(ReportHelperInterface $helper)
    {
        parent::__construct();

        $this->helper        = $helper;
        $this->accountHelper = app('FireflyIII\Helpers\Report\AccountReportHelperInterface');
        $this->budgetHelper  = app('FireflyIII\Helpers\Report\BudgetReportHelperInterface');
        $this->balanceHelper = app('FireflyIII\Helpers\Report\BalanceReportHelperInterface');

        View::share('title', trans('firefly.reports'));
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @param ARI $repository
     *
     * @return View
     * @internal param ReportHelperInterface $helper
     */
    public function index(ARI $repository)
    {
        /** @var Carbon $start */
        $start            = clone session('first');
        $months           = $this->helper->listOfMonths($start);
        $customFiscalYear = Preferences::get('customFiscalYear', 0)->data;

        // does the user have shared accounts?
        $accounts = $repository->getAccounts(['Default account', 'Asset account']);
        // get id's for quick links:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds [] = $account->id;
        }
        $accountList = join(',', $accountIds);


        return view('reports.index', compact('months', 'accounts', 'start', 'accountList', 'customFiscalYear'));
    }

    /**
     * @param string     $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     * @throws FireflyException
     */
    public function report(string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        // throw an error if necessary.
        if ($end < $start) {
            throw new FireflyException('End date cannot be before start date, silly!');
        }

        // lower threshold
        if ($start < session('first')) {
            Log::debug('Start is ' . $start . ' but sessionfirst is ' . session('first'));
            $start = session('first');
        }

        switch ($reportType) {
            default:
                throw new FireflyException('Unfortunately, reports of the type "' . e($reportType) . '" are not yet available. ');
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
            case 'audit':

                View::share(
                    'subTitle', trans(
                                  'firefly.report_audit',
                                  [
                                      'start' => $start->formatLocalized($this->monthFormat),
                                      'end'   => $end->formatLocalized($this->monthFormat),
                                  ]
                              )
                );
                View::share('subTitleIcon', 'fa-calendar');

                throw new FireflyException('Unfortunately, reports of the type "' . e($reportType) . '" are not yet available. ');
                break;
        }


    }

    /**
     * @param            $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    private function defaultMonth(string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        // get report stuff!
        $accountReport = $this->accountHelper->getAccountReport($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts);
        $budgets       = $this->budgetHelper->getBudgetReport($start, $end, $accounts);
        $categories    = $this->helper->getCategoryReport($start, $end, $accounts);
        $balance       = $this->balanceHelper->getBalanceReport($start, $end, $accounts);
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
    private function defaultMultiYear(string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {

        $incomeTopLength  = 8;
        $expenseTopLength = 8;
        // list of users stuff:
        $budgets       = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface')->getActiveBudgets();
        $categories    = app('FireflyIII\Repositories\Category\CategoryRepositoryInterface')->listCategories();
        $accountReport = $this->accountHelper->getAccountReport($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts);

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
    private function defaultYear(string $reportType, Carbon $start, Carbon $end, Collection $accounts)
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


}
