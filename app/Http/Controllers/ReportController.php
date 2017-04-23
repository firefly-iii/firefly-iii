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
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportController extends Controller
{
    /** @var ReportHelperInterface */
    protected $helper;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->helper = app(ReportHelperInterface::class);

        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.reports'));
                View::share('mainTitleIcon', 'fa-line-chart');
                View::share('subTitleIcon', 'fa-calendar');

                return $next($request);
            }
        );

    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function auditReport(Collection $accounts, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }

        View::share(
            'subTitle', trans(
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
     */
    public function budgetReport(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }

        View::share(
            'subTitle', trans(
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
     */
    public function categoryReport(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }

        View::share(
            'subTitle', trans(
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
     */
    public function defaultReport(Collection $accounts, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date'));
        }

        if ($start < session('first')) {
            $start = session('first');
        }

        View::share(
            'subTitle', trans(
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


        return view('reports.index', compact('months', 'accounts', 'start', 'accountList', 'customFiscalYear'));
    }

    /**
     * @param string $reportType
     *
     * @return mixed
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
        }

        return Response::json(['html' => $result]);
    }

    /**
     * @param ReportFormRequest $request
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
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
        $uri        = route('reports.index');

        if ($request->getAccountList()->count() === 0) {
            Log::debug('Account count is zero');
            Session::flash('error', trans('firefly.select_more_than_one_account'));

            return redirect(route('reports.index'));
        }

        if ($request->getCategoryList()->count() === 0 && $reportType === 'category') {
            Session::flash('error', trans('firefly.select_more_than_one_category'));

            return redirect(route('reports.index'));
        }

        if ($request->getBudgetList()->count() === 0 && $reportType === 'budget') {
            Session::flash('error', trans('firefly.select_more_than_one_budget'));

            return redirect(route('reports.index'));
        }

        if ($request->getTagList()->count() === 0 && $reportType === 'tag') {
            Session::flash('error', trans('firefly.select_more_than_one_tag'));

            return redirect(route('reports.index'));
        }

        if ($end < $start) {
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
     */
    public function tagReport(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date')); // @codeCoverageIgnore
        }
        if ($start < session('first')) {
            $start = session('first');
        }

        View::share(
            'subTitle', trans(
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
     */
    private function noReportOptions(): string
    {
        return view('reports.options.no-options')->render();
    }

    /**
     * @return string
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
