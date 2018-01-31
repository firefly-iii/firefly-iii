<?php
/**
 * ReportController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorFactory;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Requests\ReportFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Response;
use Session;
use View;

/**
 * Class ReportController.
 */
class ReportController extends Controller
{
    /** @var ReportHelperInterface */
    protected $helper;

    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.reports'));
                app('view')->share('mainTitleIcon', 'fa-line-chart');
                View::share('subTitleIcon', 'fa-calendar');
                $this->helper     = app(ReportHelperInterface::class);
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function accountReport(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }

        if ($start < session('first')) {
            $start = session('first');
        }
        $this->repository->cleanupBudgets();

        View::share(
            'subTitle', trans(
                          'firefly.report_account',
                          ['start' => $start->formatLocalized($this->monthFormat), 'end' => $end->formatLocalized($this->monthFormat)]
                      )
        );

        $generator = ReportGeneratorFactory::reportGenerator('Account', $start, $end);
        $generator->setAccounts($accounts);
        $generator->setExpense($expense);
        $result = $generator->generate();

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function auditReport(Collection $accounts, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }
        $this->repository->cleanupBudgets();

        View::share(
            'subTitle',
            trans(
                'firefly.report_audit',
                [
                    'start' => $start->formatLocalized($this->monthFormat),
                    'end'   => $end->formatLocalized($this->monthFormat),
                ]
            )
        );

        $generator = ReportGeneratorFactory::reportGenerator('Audit', $start, $end);
        $generator->setAccounts($accounts);
        $result = $generator->generate();

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function budgetReport(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }
        $this->repository->cleanupBudgets();

        View::share(
            'subTitle',
            trans(
                'firefly.report_budget',
                [
                    'start' => $start->formatLocalized($this->monthFormat),
                    'end'   => $end->formatLocalized($this->monthFormat),
                ]
            )
        );

        $generator = ReportGeneratorFactory::reportGenerator('Budget', $start, $end);
        $generator->setAccounts($accounts);
        $generator->setBudgets($budgets);
        $result = $generator->generate();

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function categoryReport(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }
        $this->repository->cleanupBudgets();

        View::share(
            'subTitle',
            trans(
                'firefly.report_category',
                [
                    'start' => $start->formatLocalized($this->monthFormat),
                    'end'   => $end->formatLocalized($this->monthFormat),
                ]
            )
        );

        $generator = ReportGeneratorFactory::reportGenerator('Category', $start, $end);
        $generator->setAccounts($accounts);
        $generator->setCategories($categories);
        $result = $generator->generate();

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function defaultReport(Collection $accounts, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date'));
        }

        if ($start < session('first')) {
            $start = session('first');
        }
        $this->repository->cleanupBudgets();

        View::share(
            'subTitle',
            trans(
                'firefly.report_default',
                [
                    'start' => $start->formatLocalized($this->monthFormat),
                    'end'   => $end->formatLocalized($this->monthFormat),
                ]
            )
        );

        $generator = ReportGeneratorFactory::reportGenerator('Standard', $start, $end);
        $generator->setAccounts($accounts);
        $result = $generator->generate();

        return $result;
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        /** @var Carbon $start */
        $start            = clone session('first');
        $months           = $this->helper->listOfMonths($start);
        $customFiscalYear = Preferences::get('customFiscalYear', 0)->data;
        $accounts         = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $accountList      = join(',', $accounts->pluck('id')->toArray());
        $this->repository->cleanupBudgets();

        return view('reports.index', compact('months', 'accounts', 'start', 'accountList', 'customFiscalYear'));
    }

    /**
     * @param string $reportType
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function options(string $reportType)
    {
        switch ($reportType) {
            default:
                $result = $this->noReportOptions();
                break;
            case 'category':
                $result = $this->categoryReportOptions();
                break;
            case 'budget':
                $result = $this->budgetReportOptions();
                break;
            case 'tag':
                $result = $this->tagReportOptions();
                break;
            case 'account':
                $result = $this->accountReportOptions();
                break;
        }

        return Response::json(['html' => $result]);
    }

    /**
     * @param ReportFormRequest $request
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function postIndex(ReportFormRequest $request)
    {
        // report type:
        $reportType = $request->get('report_type');
        $start      = $request->getStartDate()->format('Ymd');
        $end        = $request->getEndDate()->format('Ymd');
        $accounts   = join(',', $request->getAccountList()->pluck('id')->toArray());
        $categories = join(',', $request->getCategoryList()->pluck('id')->toArray());
        $budgets    = join(',', $request->getBudgetList()->pluck('id')->toArray());
        $tags       = join(',', $request->getTagList()->pluck('tag')->toArray());
        $expense    = join(',', $request->getExpenseList()->pluck('id')->toArray());
        $uri        = route('reports.index');

        if (0 === $request->getAccountList()->count()) {
            Log::debug('Account count is zero');
            Session::flash('error', trans('firefly.select_more_than_one_account'));

            return redirect(route('reports.index'));
        }

        if (0 === $request->getCategoryList()->count() && 'category' === $reportType) {
            Session::flash('error', trans('firefly.select_more_than_one_category'));

            return redirect(route('reports.index'));
        }

        if (0 === $request->getBudgetList()->count() && 'budget' === $reportType) {
            Session::flash('error', trans('firefly.select_more_than_one_budget'));

            return redirect(route('reports.index'));
        }

        if (0 === $request->getTagList()->count() && 'tag' === $reportType) {
            Session::flash('error', trans('firefly.select_more_than_one_tag'));

            return redirect(route('reports.index'));
        }

        if ($request->getEndDate() < $request->getStartDate()) {
            return view('error')->with('message', trans('firefly.end_after_start_date'));
        }

        switch ($reportType) {
            case 'category':
                $uri = route('reports.report.category', [$accounts, $categories, $start, $end]);
                break;
            case 'default':
                $uri = route('reports.report.default', [$accounts, $start, $end]);
                break;
            case 'audit':
                $uri = route('reports.report.audit', [$accounts, $start, $end]);
                break;
            case 'budget':
                $uri = route('reports.report.budget', [$accounts, $budgets, $start, $end]);
                break;
            case 'tag':
                $uri = route('reports.report.tag', [$accounts, $tags, $start, $end]);
                break;
            case 'account':
                $uri = route('reports.report.account', [$accounts, $expense, $start, $end]);
                break;
        }

        return redirect($uri);
    }

    /**
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function tagReport(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }
        $this->repository->cleanupBudgets();

        View::share(
            'subTitle',
            trans(
                'firefly.report_tag',
                [
                    'start' => $start->formatLocalized($this->monthFormat),
                    'end'   => $end->formatLocalized($this->monthFormat),
                ]
            )
        );

        $generator = ReportGeneratorFactory::reportGenerator('Tag', $start, $end);
        $generator->setAccounts($accounts);
        $generator->setTags($tags);
        $result = $generator->generate();

        return $result;
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    private function accountReportOptions(): string
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $expense    = $repository->getActiveAccountsByType([AccountType::EXPENSE]);
        $revenue    = $repository->getActiveAccountsByType([AccountType::REVENUE]);
        $set        = new Collection;
        $names      = $revenue->pluck('name')->toArray();
        foreach ($expense as $exp) {
            if (in_array($exp->name, $names)) {
                $set->push($exp);
            }
        }

        $result = view('reports.options.account', compact('set'))->render();

        return $result;
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    private function budgetReportOptions(): string
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getBudgets();
        $result     = view('reports.options.budget', compact('budgets'))->render();

        return $result;
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    private function categoryReportOptions(): string
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $result     = view('reports.options.category', compact('categories'))->render();

        return $result;
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    private function noReportOptions(): string
    {
        return view('reports.options.no-options')->render();
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    private function tagReportOptions(): string
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $tags       = $repository->get()->sortBy(
            function (Tag $tag) {
                return $tag->tag;
            }
        );
        $result     = view('reports.options.tag', compact('tags'))->render();

        return $result;
    }
}
