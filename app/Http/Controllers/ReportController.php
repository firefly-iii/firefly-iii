<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
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
     * @param            $report_type
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    public function defaultYear($report_type, Carbon $start, Carbon $end, Collection $accounts)
    {
        $subTitle         = trans('firefly.reportForYear', ['year' => $start->year]);
        $subTitleIcon     = 'fa-bar-chart';
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        $accountReport = $this->helper->getAccountReportForList($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReportForList($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReportForList($start, $end, $accounts);

        Session::flash('gaEventCategory', 'report');
        Session::flash('gaEventAction', 'year');
        Session::flash('gaEventLabel', $start->format('Y'));

        // and some id's, joined:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountIds = join(';', $accountIds);

        return view(
            'reports.default.year',
            compact(
                'start', 'accountReport', 'incomes', 'report_type', 'accountIds', 'end',
                'expenses', 'subTitle', 'subTitleIcon', 'incomeTopLength', 'expenseTopLength'
            )
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
    public function defaultMonth($report_type, Carbon $start, Carbon $end, Collection $accounts)
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
            'reports.default.month',
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
        // throw an error if necessary.
        if ($end < $start) {
            return view('error')->with('message', 'End date cannot be before start date, silly!');
        }

        // more than two months date difference means year report.
        if ($start->diffInMonths($end) > 1) {
            return $this->defaultYear($report_type, $start, $end, $accounts);
        }

        return $this->defaultMonth($report_type, $start, $end, $accounts);


    }


}
