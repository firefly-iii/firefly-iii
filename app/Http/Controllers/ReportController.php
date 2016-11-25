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
use FireflyIII\Generator\Report\ReportGeneratorFactory;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Requests\ReportFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.reports'));
                View::share('mainTitleIcon', 'fa-line-chart');
                View::share('subTitleIcon', 'fa-calendar');

                $this->helper = app(ReportHelperInterface::class);

                return $next($request);
            }
        );

    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     * @throws FireflyException
     */
    public function auditReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date'));
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
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @param Collection $categories
     *
     * @return string
     */
    public function categoryReport(Carbon $start, Carbon $end, Collection $accounts, Collection $categories)
    {
        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date'));
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
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     * @throws FireflyException
     */
    public function defaultReport(Carbon $start, Carbon $end, Collection $accounts)
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
        }

        return Response::json(['html' => $result]);
    }

    /**
     * @param ReportFormRequest $request
     *
     * @return RedirectResponse
     * @throws FireflyException
     */
    public function postIndex(ReportFormRequest $request): RedirectResponse
    {
        // report type:
        $reportType = $request->get('report_type');
        $start      = $request->getStartDate()->format('Ymd');
        $end        = $request->getEndDate()->format('Ymd');
        $accounts   = join(',', $request->getAccountList()->pluck('id')->toArray());
        $categories = join(',', $request->getCategoryList()->pluck('id')->toArray());

        if ($request->getAccountList()->count() === 0) {
            Session::flash('error', trans('firefly.select_more_than_one_account'));

            return redirect(route('reports.index'));
        }

        if ($end < $start) {
            return view('error')->with('message', trans('firefly.end_after_start_date'));
        }

        // lower threshold
        if ($start < session('first')) {
            $start = session('first');
        }

        switch ($reportType) {
            default:
                throw new FireflyException(sprintf('Firefly does not support the "%s"-report yet.', $reportType));
            case 'category':
                $uri = route('reports.report.category', [$start, $end, $accounts, $categories]);
                break;
            case 'default':
                $uri = route('reports.report.default', [$start, $end, $accounts]);
                break;
            case 'audit':
                $uri = route('reports.report.audit', [$start, $end, $accounts]);
                break;
        }

        return redirect($uri);
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
}
