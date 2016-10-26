<?php
/**
 * ReportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
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
    /** @var BudgetReportHelperInterface */
    protected $budgetHelper;
    /** @var ReportHelperInterface */
    protected $helper;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        View::share('title', trans('firefly.reports'));
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $this->createRepositories();
        /** @var Carbon $start */
        $start            = clone session('first');
        $months           = $this->helper->listOfMonths($start);
        $customFiscalYear = Preferences::get('customFiscalYear', 0)->data;

        // does the user have shared accounts?
        $accounts = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
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
        $this->createRepositories();
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
                throw new FireflyException('Unfortunately, reports of the type "' . e($reportType) . '" are not available at this time.');
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
        /** @var AccountTaskerInterface $tasker */
        $tasker    = app(AccountTaskerInterface::class);
        $auditData = [];
        $dayBefore = clone $start;
        $dayBefore->subDay();
        /** @var Account $account */
        foreach ($accounts as $account) {
            // balance the day before:
            $id               = $account->id;
            $dayBeforeBalance = Steam::balance($account, $dayBefore);
            $journals         = $tasker->getJournalsInPeriod(new Collection([$account]), [], $start, $end);
            $journals         = $journals->reverse();
            $startBalance     = $dayBeforeBalance;


            /** @var Transaction $journal */
            foreach ($journals as $transaction) {
                $transaction->before = $startBalance;
                $transactionAmount   = $transaction->transaction_amount;
                $newBalance          = bcadd($startBalance, $transactionAmount);
                $transaction->after  = $newBalance;
                $startBalance        = $newBalance;
            }

            /*
             * Reverse set again.
             */
            $auditData[$id]['journals']         = $journals->reverse();
            $auditData[$id]['exists']           = $journals->count() > 0;
            $auditData[$id]['end']              = $end->formatLocalized(strval(trans('config.month_and_day')));
            $auditData[$id]['endBalance']       = Steam::balance($account, $end);
            $auditData[$id]['dayBefore']        = $dayBefore->formatLocalized(strval(trans('config.month_and_day')));
            $auditData[$id]['dayBeforeBalance'] = $dayBeforeBalance;
        }

        $reportType = 'audit';
        $accountIds = join(',', $accounts->pluck('id')->toArray());

        $hideable    = ['buttons', 'icon', 'description', 'balance_before', 'amount', 'balance_after', 'date',
                        'interest_date', 'book_date', 'process_date',
                        // three new optional fields.
                        'due_date', 'payment_date', 'invoice_date',
                        'from', 'to', 'budget', 'category', 'bill',
                        // more new optional fields
                        'internal_reference', 'notes',

                        'create_date', 'update_date',
        ];
        $defaultShow = ['icon', 'description', 'balance_before', 'amount', 'balance_after', 'date', 'to'];

        return view('reports.audit.report', compact('start', 'end', 'reportType', 'accountIds', 'accounts', 'auditData', 'hideable', 'defaultShow'));
    }

    /**
     *
     */
    private function createRepositories()
    {
        $this->helper       = app(ReportHelperInterface::class);
        $this->budgetHelper = app(BudgetReportHelperInterface::class);
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
        // get report stuff!
        $budgets = $this->budgetHelper->getBudgetReport($start, $end, $accounts);
        $bills   = $this->helper->getBillReport($start, $end, $accounts);
        $tags    = $this->helper->tagReport($start, $end, $accounts);

        // and some id's, joined:
        $accountIds = join(',', $accounts->pluck('id')->toArray());

        // continue!
        return view(
            'reports.default.month',
            compact(
                'start', 'end',
                'tags',
                'budgets',
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

        $budgets    = app(BudgetRepositoryInterface::class)->getActiveBudgets();
        $categories = app(CategoryRepositoryInterface::class)->getCategories();
        $tags       = $this->helper->tagReport($start, $end, $accounts);

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
                'budgets', 'accounts', 'categories', 'start', 'end', 'accountIds', 'reportType', 'tags'
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
        $tags    = $this->helper->tagReport($start, $end, $accounts);
        $budgets = $this->budgetHelper->budgetYearOverview($start, $end, $accounts);

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
                'start', 'reportType', 'accountIds', 'end', 'tags', 'budgets'
            )
        );
    }
}
