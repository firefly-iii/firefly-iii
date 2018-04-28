<?php
/**
 * BillController.php
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

use ExpandedForm;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;
use Preferences;
use Symfony\Component\HttpFoundation\ParameterBag;
use URL;
use View;

/**
 * Class BillController.
 */
class BillController extends Controller
{
    /** @var AttachmentHelperInterface Helper for attachments. */
    private $attachments;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $maxFileSize = app('steam')->phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = app('steam')->phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        View::share('uploadSize', $uploadSize);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.bills'));
                app('view')->share('mainTitleIcon', 'fa-calendar-o');
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request                     $request
     *
     * @param CurrencyRepositoryInterface $repository
     *
     * @return View
     */
    public function create(Request $request, CurrencyRepositoryInterface $repository)
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = strtolower((string)trans('firefly.repeat_freq_' . $current));
        }
        $subTitle        = trans('firefly.create_new_bill');
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $currencies      = ExpandedForm::makeSelectList($repository->get());

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('bills.create.fromStore')) {
            $this->rememberPreviousUri('bills.create.uri');
        }
        $request->session()->forget('bills.create.fromStore');

        return view('bills.create', compact('periods', 'subTitle', 'currencies', 'defaultCurrency'));
    }

    /**
     * @param Bill $bill
     *
     * @return View
     */
    public function delete(Bill $bill)
    {
        // put previous url in session
        $this->rememberPreviousUri('bills.delete.uri');
        $subTitle = trans('firefly.delete_bill', ['name' => $bill->name]);

        return view('bills.delete', compact('bill', 'subTitle'));
    }

    /**
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        $name = $bill->name;
        $repository->destroy($bill);

        $request->session()->flash('success', (string)trans('firefly.deleted_bill', ['name' => $name]));
        Preferences::mark();

        return redirect($this->getPreviousUri('bills.delete.uri'));
    }

    /**
     * @param Request                     $request
     * @param CurrencyRepositoryInterface $repository
     * @param Bill                        $bill
     *
     * @return View
     */
    public function edit(Request $request, CurrencyRepositoryInterface $repository, Bill $bill)
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = trans('firefly.' . $current);
        }
        $subTitle = trans('firefly.edit_bill', ['name' => $bill->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('bills.edit.fromUpdate')) {
            $this->rememberPreviousUri('bills.edit.uri');
        }

        $currency         = app('amount')->getDefaultCurrency();
        $bill->amount_min = round($bill->amount_min, $currency->decimal_places);
        $bill->amount_max = round($bill->amount_max, $currency->decimal_places);
        $defaultCurrency  = app('amount')->getDefaultCurrency();
        $currencies       = ExpandedForm::makeSelectList($repository->get());

        $preFilled = [
            'notes' => '',
        ];

        /** @var Note $note */
        $note = $bill->notes()->first();
        if (null !== $note) {
            $preFilled['notes'] = $note->text;
        }

        $request->session()->flash('preFilled', $preFilled);

        $request->session()->forget('bills.edit.fromUpdate');

        return view('bills.edit', compact('subTitle', 'periods', 'bill', 'defaultCurrency', 'currencies'));
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return View
     */
    public function index(BillRepositoryInterface $repository)
    {
        $start      = session('start');
        $end        = session('end');
        $pageSize   = (int)Preferences::get('listPageSize', 50)->data;
        $paginator  = $repository->getPaginator($pageSize);
        $parameters = new ParameterBag();
        $parameters->set('start', $start);
        $parameters->set('end', $end);
        $transformer = new BillTransformer($parameters);
        /** @var Collection $bills */
        $bills = $paginator->getCollection()->map(
            function (Bill $bill) use ($transformer) {
                return $transformer->transform($bill);
            }
        );
        $bills = $bills->sortBy(
            function (array $bill) {
                return (int)!$bill['active'] . strtolower($bill['name']);
            }
        );

        // add info about rules:
        $rules = $repository->getRulesForBills($paginator->getCollection());
        $bills = $bills->map(
            function (array $bill) use ($rules) {
                $bill['rules'] = $rules[$bill['id']] ?? [];

                return $bill;
            }
        );

        $paginator->setPath(route('bills.index'));

        return view('bills.index', compact('bills', 'paginator'));
    }

    /**
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function rescan(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        if (0 === (int)$bill->active) {
            $request->session()->flash('warning', (string)trans('firefly.cannot_scan_inactive_bill'));

            return redirect(URL::previous());
        }
        $set   = $repository->getRulesForBill($bill);
        $total = 0;
        foreach ($set as $rule) {
            // simply fire off all rules?
            /** @var TransactionMatcher $matcher */
            $matcher = app(TransactionMatcher::class);
            $matcher->setLimit(100000); // large upper limit
            $matcher->setRange(100000); // large upper limit
            $matcher->setRule($rule);
            $matchingTransactions = $matcher->findTransactionsByRule();
            $total                += $matchingTransactions->count();
            $repository->linkCollectionToBill($bill, $matchingTransactions);
        }


        $request->session()->flash('success', (string)trans('firefly.rescanned_bill', ['total' => $total]));
        Preferences::mark();

        return redirect(URL::previous());
    }

    /**
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return View
     */
    public function show(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        // add info about rules:
        $rules          = $repository->getRulesForBill($bill);
        $subTitle       = $bill->name;
        $start          = session('start');
        $end            = session('end');
        $year           = $start->year;
        $page           = (int)$request->get('page');
        $pageSize       = (int)Preferences::get('listPageSize', 50)->data;
        $yearAverage    = $repository->getYearAverage($bill, $start);
        $overallAverage = $repository->getOverallAverage($bill);
        $manager        = new Manager();
        $manager->setSerializer(new DataArraySerializer());
        $manager->parseIncludes(['attachments', 'notes']);

        // Make a resource out of the data and
        $parameters = new ParameterBag();
        $parameters->set('start', $start);
        $parameters->set('end', $end);
        $resource = new Item($bill, new BillTransformer($parameters), 'bill');
        $object   = $manager->createData($resource)->toArray();

        // use collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setBills(new Collection([$bill]))->setLimit($pageSize)->setPage($page)->withBudgetInformation()
                  ->withCategoryInformation();
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('bills.show', [$bill->id]));


        return view('bills.show', compact('transactions', 'rules', 'yearAverage', 'overallAverage', 'year', 'object', 'bill', 'subTitle'));
    }

    /**
     * @param BillFormRequest              $request
     * @param BillRepositoryInterface      $repository
     *
     * @param RuleGroupRepositoryInterface $ruleGroupRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BillFormRequest $request, BillRepositoryInterface $repository, RuleGroupRepositoryInterface $ruleGroupRepository)
    {
        $billData = $request->getBillData();
        $bill     = $repository->store($billData);
        if (null === $bill) {
            $request->session()->flash('error', (string)trans('firefly.bill_store_error'));

            return redirect(route('bills.create'))->withInput();
        }
        $request->session()->flash('success', (string)trans('firefly.stored_new_bill', ['name' => $bill->name]));
        Preferences::mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($bill, $files);

        // flash messages
        if (\count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }

        // do return to original bill form?
        $return = 'false';
        if (1 === (int)$request->get('create_another')) {
            $return = 'true';
        }

        // find first rule group, or create one:
        $count = $ruleGroupRepository->count();
        if ($count === 0) {
            $data  = [
                'title'       => (string)trans('firefly.rulegroup_for_bills_title'),
                'description' => (string)trans('firefly.rulegroup_for_bills_description'),
            ];
            $group = $ruleGroupRepository->store($data);
        }
        if ($count > 0) {
            $group = $ruleGroupRepository->getActiveGroups(auth()->user())->first();
        }

        // redirect to page that will create a new rule.
        $params = http_build_query(['fromBill' => $bill->id, 'return' => $return]);

        return redirect(route('rules.create', [$group->id]) . '?' . $params);
    }

    /**
     * @param BillFormRequest         $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BillFormRequest $request, BillRepositoryInterface $repository, Bill $bill)
    {
        $billData = $request->getBillData();
        $bill     = $repository->update($bill, $billData);

        $request->session()->flash('success', (string)trans('firefly.updated_bill', ['name' => $bill->name]));
        Preferences::mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($bill, $files);

        // flash messages
        if (\count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.edit.fromUpdate', true);

            return redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('bills.edit.uri'));
    }
}
