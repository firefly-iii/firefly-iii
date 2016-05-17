<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Report\AccountReportHelperInterface;
use FireflyIII\Helpers\Report\BalanceReportHelperInterface;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Preferences;
use Session;
use Steam;
use View;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportController extends Controller
{

    /** @var AccountReportHelperInterface */
    protected $accountHelper;
    /** @var BalanceReportHelperInterface */
    protected $balanceHelper;

    /** @var BudgetReportHelperInterface */
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
        $this->accountHelper = app(AccountReportHelperInterface::class);
        $this->budgetHelper  = app(BudgetReportHelperInterface::class);
        $this->balanceHelper = app(BalanceReportHelperInterface::class);

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
        $accounts = $repository->getAccountsByType(['Default account', 'Asset account']);
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
            $start = session('first');
        }

        View::share(
            'subTitle', trans(
                          'firefly.report_' . $reportType,
                          [
                              'start' => $start->formatLocalized($this->monthFormat),
                              'end'   => $end->formatLocalized($this->monthFormat),
                          ]
                      )
        );
        View::share('subTitleIcon', 'fa-calendar');

        switch ($reportType) {
            default:
                throw new FireflyException('Unfortunately, reports of the type "' . e($reportType) . '" are not yet available. ');
            case 'default':

                // more than one year date difference means year report.
                if ($start->diffInMonths($end) > 12) {
                    return $this->defaultMultiYear($reportType, $start, $end, $accounts);
                }
                // more than two months date difference means year report.
                if ($start->diffInMonths($end) > 1) {
                    return $this->defaultYear($reportType, $start, $end, $accounts);
                }

                // otherwise default
                return $this->defaultMonth($reportType, $start, $end, $accounts);
            case 'audit':
                // always default
                return $this->auditReport($start, $end, $accounts);
        }


    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return View
     */
    private function auditReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        /** @var ARI $repos */
        $repos     = app(ARI::class);
        $auditData = [];
        $dayBefore = clone $start;
        $dayBefore->subDay();
        /** @var Account $account */
        foreach ($accounts as $account) {

            // balance the day before:
            $id               = $account->id;
            $first            = $repos->oldestJournalDate($account);
            $last             = $repos->newestJournalDate($account);
            $exists           = false;
            $journals         = new Collection;
            $dayBeforeBalance = Steam::balance($account, $dayBefore);
            /*
             * Is there even activity on this account between the requested dates?
             */
            if ($start->between($first, $last) || $end->between($first, $last)) {
                $exists   = true;
                $journals = $repos->journalsInPeriod($accounts, [], $start, $end);

            }
            /*
             * Reverse set, get balances.
             */
            $journals     = $journals->reverse();
            $startBalance = $dayBeforeBalance;
            /** @var TransactionJournal $journal */
            foreach ($journals as $journal) {
                $journal->before   = $startBalance;
                $transactionAmount = $journal->source_amount;

                // get currently relevant transaction:
                if (intval($journal->destination_account_id) === $account->id) {
                    $transactionAmount = $journal->destination_amount;
                }
                $newBalance     = bcadd($startBalance, $transactionAmount);
                $journal->after = $newBalance;
                $startBalance   = $newBalance;

            }

            /*
             * Reverse set again.
             */
            $auditData[$id]['journals']         = $journals->reverse();
            $auditData[$id]['exists']           = $exists;
            $auditData[$id]['end']              = $end->formatLocalized(trans('config.month_and_day'));
            $auditData[$id]['endBalance']       = Steam::balance($account, $end);
            $auditData[$id]['dayBefore']        = $dayBefore->formatLocalized(trans('config.month_and_day'));
            $auditData[$id]['dayBeforeBalance'] = $dayBeforeBalance;
        }

        $reportType = 'audit';
        $accountIds = join(',', $accounts->pluck('id')->toArray());

        $hideable    = ['buttons', 'icon', 'description', 'balance_before', 'amount', 'balance_after', 'date', 'book_date', 'process_date', 'interest_date',
                        'from', 'to', 'budget', 'category', 'bill', 'create_date', 'update_date',
        ];
        $defaultShow = ['icon', 'description', 'balance_before', 'amount', 'balance_after', 'date', 'to'];

        return view('reports.audit.report', compact('start', 'end', 'reportType', 'accountIds', 'accounts', 'auditData', 'hideable', 'defaultShow'));
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
        $tags          = $this->helper->tagReport($start, $end, $accounts);

        // and some id's, joined:
        $accountIds = join(',', $accounts->pluck('id')->toArray());

        // continue!
        return view(
            'reports.default.month',
            compact(
                'start', 'end', 'reportType',
                'accountReport', 'tags',
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
        $budgets       = app(BudgetRepositoryInterface::class)->getActiveBudgets();
        $categories    = app(CategoryRepositoryInterface::class)->getCategories();
        $accountReport = $this->accountHelper->getAccountReport($start, $end, $accounts);
        $incomes       = $this->helper->getIncomeReport($start, $end, $accounts);
        $expenses      = $this->helper->getExpenseReport($start, $end, $accounts);
        $tags          = $this->helper->tagReport($start, $end, $accounts);

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
                'incomeTopLength', 'expenseTopLength', 'tags'
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
        $tags          = $this->helper->tagReport($start, $end, $accounts);

        // find the budgets we've spent money on this period with these accounts:
        $budgets = $this->budgetHelper->getBudgetsWithExpenses($start, $end, $accounts);

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
                'expenses', 'incomeTopLength', 'expenseTopLength', 'tags', 'budgets'
            )
        );
    }
}
