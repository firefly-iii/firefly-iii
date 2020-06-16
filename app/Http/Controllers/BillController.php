<?php
/**
 * BillController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BillController.
 *
 */
class BillController extends Controller
{
    /** @var AttachmentHelperInterface Helper for attachments. */
    private $attachments;
    /** @var BillRepositoryInterface Bill repository */
    private $billRepository;

    /**
     * BillController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('showBudget', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.bills'));
                app('view')->share('mainTitleIcon', 'fa-calendar-o');
                $this->attachments    = app(AttachmentHelperInterface::class);
                $this->billRepository = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a new bill.
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {
        $periods = [];
        /** @var array $billPeriods */
        $billPeriods = config('firefly.bill_periods');
        foreach ($billPeriods as $current) {
            $periods[$current] = strtolower((string) trans('firefly.repeat_freq_' . $current));
        }
        $subTitle        = (string) trans('firefly.create_new_bill');
        $defaultCurrency = app('amount')->getDefaultCurrency();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('bills.create.fromStore')) {
            $this->rememberPreviousUri('bills.create.uri');
        }
        $request->session()->forget('bills.create.fromStore');

        return view('bills.create', compact('periods', 'subTitle', 'defaultCurrency'));
    }

    /**
     * Delete a bill.
     *
     * @param Bill $bill
     *
     * @return Factory|View
     */
    public function delete(Bill $bill)
    {
        // put previous url in session
        $this->rememberPreviousUri('bills.delete.uri');
        $subTitle = (string) trans('firefly.delete_bill', ['name' => $bill->name]);

        return view('bills.delete', compact('bill', 'subTitle'));
    }

    /**
     * Destroy a bill.
     *
     * @param Request $request
     * @param Bill    $bill
     *
     * @return RedirectResponse|Redirector
     */
    public function destroy(Request $request, Bill $bill)
    {
        $name = $bill->name;
        $this->billRepository->destroy($bill);

        $request->session()->flash('success', (string) trans('firefly.deleted_bill', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('bills.delete.uri'));
    }

    /**
     * Edit a bill.
     *
     * @param Request $request
     * @param Bill    $bill
     *
     * @return Factory|View
     */
    public function edit(Request $request, Bill $bill)
    {
        $periods = [];
        /** @var array $billPeriods */
        $billPeriods = config('firefly.bill_periods');

        foreach ($billPeriods as $current) {
            $periods[$current] = (string) trans('firefly.' . $current);
        }

        $subTitle = (string) trans('firefly.edit_bill', ['name' => $bill->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('bills.edit.fromUpdate')) {
            $this->rememberPreviousUri('bills.edit.uri');
        }

        $currency         = app('amount')->getDefaultCurrency();
        $bill->amount_min = round((float) $bill->amount_min, $currency->decimal_places);
        $bill->amount_max = round((float) $bill->amount_max, $currency->decimal_places);
        $rules            = $this->billRepository->getRulesForBill($bill);
        $defaultCurrency  = app('amount')->getDefaultCurrency();

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');

        $preFilled = [
            'notes'                   => $this->billRepository->getNoteText($bill),
            'transaction_currency_id' => $bill->transaction_currency_id,
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $bill->active,
        ];

        $request->session()->flash('preFilled', $preFilled);
        $request->session()->forget('bills.edit.fromUpdate');

        return view('bills.edit', compact('subTitle', 'periods', 'rules', 'bill', 'defaultCurrency', 'preFilled'));
    }

    /**
     * Show all bills.
     *
     * @return Factory|View
     */
    public function index()
    {
        $start           = session('start');
        $end             = session('end');
        $unfiltered      = $this->billRepository->getBills();

        $defaultCurrency = app('amount')->getDefaultCurrency();
        $parameters      = new ParameterBag();
        $parameters->set('start', $start);
        $parameters->set('end', $end);

        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($parameters);

        /** @var Collection $bills */
        $bills = $unfiltered->map(
            function (Bill $bill) use ($transformer, $defaultCurrency) {
                $return                            = $transformer->transform($bill);
                $nextExpectedMatch = new Carbon($return['next_expected_match']);
                $return['next_expected_match_diff'] = $nextExpectedMatch->isToday() ? trans('firefly.today') : $nextExpectedMatch->diffForHumans(today(), Carbon::DIFF_RELATIVE_TO_NOW);
                $currency                          = $bill->transactionCurrency ?? $defaultCurrency;
                $return['currency_id']             = $currency->id;
                $return['currency_name']           = $currency->name;
                $return['currency_symbol']         = $currency->symbol;
                $return['currency_code']           = $currency->code;
                $return['currency_decimal_places'] = $currency->decimal_places;
                $return['attachments']             = $this->billRepository->getAttachments($bill);

                return $return;
            }
        );

        // add info about rules:
        $rules = $this->billRepository->getRulesForBills($unfiltered);
        $bills = $bills->map(
            static function (array $bill) use ($rules) {
                $bill['rules'] = $rules[$bill['id']] ?? [];

                return $bill;
            }
        );

        // summarise per currency:
        $sums = $this->getSums($bills);

        return view('bills.index', compact('bills', 'sums'));
    }

    /**
     * Rescan bills for transactions.
     *
     * @param Request $request
     * @param Bill    $bill
     *
     * @throws FireflyException
     * @return RedirectResponse|Redirector
     */
    public function rescan(Request $request, Bill $bill)
    {
        $total = 0;
        if (false === $bill->active) {
            $request->session()->flash('warning', (string) trans('firefly.cannot_scan_inactive_bill'));

            return redirect(route('bills.show', [$bill->id]));
        }
        $set = new Collection;
        if (true === $bill->active) {
            $set   = $this->billRepository->getRulesForBill($bill);
            $total = 0;
        }
        if (0 === $set->count()) {
            $request->session()->flash('error', (string) trans('firefly.no_rules_for_bill'));

            return redirect(route('bills.show', [$bill->id]));
        }

        // unlink all journals:
        $this->billRepository->unlinkAll($bill);

        foreach ($set as $rule) {
            // simply fire off all rules?
            /** @var TransactionMatcher $matcher */
            $matcher = app(TransactionMatcher::class);
            $matcher->setSearchLimit(100000); // large upper limit
            $matcher->setTriggeredLimit(100000); // large upper limit
            $matcher->setRule($rule);
            $matchingTransactions = $matcher->findTransactionsByRule();
            $total                += count($matchingTransactions);
            $this->billRepository->linkCollectionToBill($bill, $matchingTransactions);
        }


        $request->session()->flash('success', (string) trans_choice('firefly.rescanned_bill', $total));
        app('preferences')->mark();

        return redirect(route('bills.show', [$bill->id]));
    }

    /**
     * Show a bill.
     *
     * @param Request $request
     * @param Bill    $bill
     *
     * @return Factory|View
     */
    public function show(Request $request, Bill $bill)
    {
        // add info about rules:
        $rules    = $this->billRepository->getRulesForBill($bill);
        $subTitle = $bill->name;
        /** @var Carbon $start */
        $start = session('start');
        /** @var Carbon $end */
        $end            = session('end');
        $year           = $start->year;
        $page           = (int) $request->get('page');
        $pageSize       = (int) app('preferences')->get('listPageSize', 50)->data;
        $yearAverage    = $this->billRepository->getYearAverage($bill, $start);
        $overallAverage = $this->billRepository->getOverallAverage($bill);
        $manager        = new Manager();
        $manager->setSerializer(new DataArraySerializer());
        $manager->parseIncludes(['attachments', 'notes']);

        // Make a resource out of the data and
        $parameters = new ParameterBag();
        $parameters->set('start', $start);
        $parameters->set('end', $end);

        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($parameters);

        $resource                   = new Item($bill, $transformer, 'bill');
        $object                     = $manager->createData($resource)->toArray();
        $object['data']['currency'] = $bill->transactionCurrency;

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setBill($bill)->setLimit($pageSize)->setPage($page)->withBudgetInformation()
                  ->withCategoryInformation()->withAccountInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('bills.show', [$bill->id]));

        // transform any attachments as well.
        $collection  = $this->billRepository->getAttachments($bill);
        $attachments = new Collection;

        // @codeCoverageIgnoreStart
        if ($collection->count() > 0) {
            /** @var AttachmentTransformer $transformer */
            $transformer = app(AttachmentTransformer::class);
            $attachments = $collection->each(
                static function (Attachment $attachment) use ($transformer) {
                    return $transformer->transform($attachment);
                }
            );
        }

        // @codeCoverageIgnoreEnd


        return view('bills.show', compact('attachments', 'groups', 'rules', 'yearAverage', 'overallAverage', 'year', 'object', 'bill', 'subTitle'));
    }


    /**
     * Store a new bill.
     *
     * @param BillFormRequest $request
     *
     * @return RedirectResponse
     *
     */
    public function store(BillFormRequest $request): RedirectResponse
    {
        $billData           = $request->getBillData();
        $billData['active'] = true;
        try {
            $bill = $this->billRepository->store($billData);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            $request->session()->flash('error', (string) trans('firefly.bill_store_error'));

            return redirect(route('bills.create'))->withInput();
        }
        $request->session()->flash('success', (string) trans('firefly.stored_new_bill', ['name' => $bill->name]));
        app('preferences')->mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($bill, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info',(string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }

        return redirect(route('rules.create-from-bill', [$bill->id]));
    }

    /**
     * Update a bill.
     *
     * @param BillFormRequest $request
     * @param Bill            $bill
     *
     * @return RedirectResponse
     */
    public function update(BillFormRequest $request, Bill $bill): RedirectResponse
    {
        $billData = $request->getBillData();
        $bill     = $this->billRepository->update($bill, $billData);

        $request->session()->flash('success', (string) trans('firefly.updated_bill', ['name' => $bill->name]));
        app('preferences')->mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($bill, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info',(string)trans('firefly.no_att_demo_user'));
        }

        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }
        $redirect = redirect($this->getPreviousUri('bills.edit.uri'));

        if (1 === (int) $request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.edit.fromUpdate', true);

            $redirect = redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }

    /**
     * @param Collection $bills
     *
     * @return array
     */
    private function getSums(Collection $bills): array
    {
        $sums = [];

        /** @var array $bill */
        foreach ($bills as $bill) {
            if (false === $bill['active']) {
                continue;
            }
            if (0 === count($bill['pay_dates'])) {
                continue;
            }
            /** @var TransactionCurrency $currency */
            $currencyId        = $bill['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currencyId,
                    'currency_code'           => $bill['currency_code'],
                    'currency_name'           => $bill['currency_name'],
                    'currency_symbol'         => $bill['currency_symbol'],
                    'currency_decimal_places' => $bill['currency_decimal_places'],
                    'avg'                     => '0',
                ];

            $avg                      = bcdiv(bcadd((string) $bill['amount_min'], (string) $bill['amount_max']), '2');
            $avg                      = bcmul($avg, (string) count($bill['pay_dates']));
            $sums[$currencyId]['avg'] = bcadd($sums[$currencyId]['avg'], $avg);
        }

        return $sums;
    }
}
