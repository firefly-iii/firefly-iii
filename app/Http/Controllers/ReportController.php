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
use FireflyIII\Generator\Report\Standard\MonthReportGenerator;
use FireflyIII\Generator\Report\StandardReportGenerator;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Requests\ReportFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Preferences;
use Response;
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
                          'firefly.report_audit',
                          [
                              'start' => $start->formatLocalized($this->monthFormat),
                              'end'   => $end->formatLocalized($this->monthFormat),
                          ]
                      )
        );
        View::share('subTitleIcon', 'fa-calendar');

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
     * @return string
     * @throws FireflyException
     */
    public function defaultReport(Carbon $start, Carbon $end, Collection $accounts)
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
                          'firefly.report_default',
                          [
                              'start' => $start->formatLocalized($this->monthFormat),
                              'end'   => $end->formatLocalized($this->monthFormat),
                          ]
                      )
        );
        View::share('subTitleIcon', 'fa-calendar');

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
     * @param string $reportType
     *
     * @return mixed
     */
    public function options(string $reportType)
    {
        $result = '';
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

        if ($end < $start) {
            throw new FireflyException('End date cannot be before start date, silly!');
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
