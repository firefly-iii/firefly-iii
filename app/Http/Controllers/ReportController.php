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
        $accounts = $repository->getAccounts(['Default account', 'Asset account']);
        // get id's for quick links:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds [] = $account->id;
        }
        $accountList = join(',', $accountIds);


        return view('reports.index', compact('months', 'accounts', 'start', 'accountList'));
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
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        $accountReport = $this->helper->getAccountReport($start, $end, $accounts);
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
                'start', 'accountReport', 'incomes', 'report_type', 'accountIds', 'end',
                'expenses', 'incomeTopLength', 'expenseTopLength'
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
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        // get report stuff!
        $accountReport = $this->helper->getAccountReport($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts);
        $budgets       = $this->helper->getBudgetReport($start, $end, $accounts);
        $categories    = $this->helper->getCategoryReport($start, $end, $accounts);
        $balance       = $this->helper->getBalanceReport($start, $end, $accounts);
        $bills         = $this->helper->getBillReport($start, $end, $accounts);

        // and some id's, joined:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountIds = join(',', $accountIds);

        // continue!
        return view(
            'reports.default.month',
            compact(
                'start', 'end', 'report_type',
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

    public function defaultMultiYear($report_type, $start, $end, $accounts)
    {


        // list of users stuff:
        $budgets    = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface')->getActiveBudgets();
        $categories = app('FireflyIII\Repositories\Category\CategoryRepositoryInterface')->getCategories();

        // and some id's, joined:
        $accountIds = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountIds = join(',', $accountIds);

        return view(
            'reports.default.multi-year', compact('budgets', 'accounts', 'categories', 'start', 'end', 'accountIds', 'report_type')
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

        // lower threshold
        if ($start < Session::get('first')) {
            $start = Session::get('first');
        }

        switch ($report_type) {
            default:
            case 'default':

                View::share(
                    'subTitle', trans(
                                  'firefly.report_default',
                                  [
                                      'start' => $start->formatLocalized($this->monthFormat),
                                      'end'   => $end->formatLocalized($this->monthFormat)
                                  ]
                              )
                );
                View::share('subTitleIcon', 'fa-calendar');

                // more than one year date difference means year report.
                if ($start->diffInMonths($end) > 12) {
                    //                    return view('error')->with('message', 'No report yet for this time period.');
                    return $this->defaultMultiYear($report_type, $start, $end, $accounts);
                }
                // more than two months date difference means year report.
                if ($start->diffInMonths($end) > 1) {
                    return $this->defaultYear($report_type, $start, $end, $accounts);
                }

                return $this->defaultMonth($report_type, $start, $end, $accounts);
                break;
        }


    }


}
