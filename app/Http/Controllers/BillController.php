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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Preferences;
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
                View::share('title', trans('firefly.bills'));
                View::share('mainTitleIcon', 'fa-calendar-o');
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = trans('firefly.' . $current);
        }
        $subTitle = trans('firefly.create_new_bill');

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('bills.create.fromStore')) {
            $this->rememberPreviousUri('bills.create.uri');
        }
        $request->session()->forget('bills.create.fromStore');
        $request->session()->flash('gaEventCategory', 'bills');
        $request->session()->flash('gaEventAction', 'create');

        return view('bills.create', compact('periods', 'subTitle'));
    }

    /**
     * @param Request $request
     * @param Bill    $bill
     *
     * @return View
     */
    public function delete(Request $request, Bill $bill)
    {
        // put previous url in session
        $this->rememberPreviousUri('bills.delete.uri');
        $request->session()->flash('gaEventCategory', 'bills');
        $request->session()->flash('gaEventAction', 'delete');
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

        $request->session()->flash('success', strval(trans('firefly.deleted_bill', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('bills.delete.uri'));
    }

    /**
     * @param Request $request
     * @param Bill    $bill
     *
     * @return View
     */
    public function edit(Request $request, Bill $bill)
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
        $request->session()->flash('gaEventCategory', 'bills');
        $request->session()->flash('gaEventAction', 'edit');

        return view('bills.edit', compact('subTitle', 'periods', 'bill'));
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return View
     */
    public function index(BillRepositoryInterface $repository)
    {
        /** @var Carbon $start */
        $start = session('start');
        /** @var Carbon $end */
        $end = session('end');

        $bills = $repository->getBills();
        $bills->each(
            function (Bill $bill) use ($repository, $start, $end) {
                // paid in this period?
                $bill->paidDates = $repository->getPaidDatesInRange($bill, $start, $end);
                $bill->payDates  = $repository->getPayDatesInRange($bill, $start, $end);
                $lastPaidDate    = $this->lastPaidDate($repository->getPaidDatesInRange($bill, $start, $end), $start);
                if ($bill->paidDates->count() >= $bill->payDates->count()) {
                    // if all bills have been been paid, jump to next period.
                    $lastPaidDate = $end;
                }
                $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill, $lastPaidDate);
            }
        );

        return view('bills.index', compact('bills'));
    }

    /**
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function rescan(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        if (0 === intval($bill->active)) {
            $request->session()->flash('warning', strval(trans('firefly.cannot_scan_inactive_bill')));

            return redirect(URL::previous());
        }

        $journals = $repository->getPossiblyRelatedJournals($bill);
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $repository->scan($bill, $journal);
        }

        $request->session()->flash('success', strval(trans('firefly.rescanned_bill')));
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
        /** @var Carbon $date */
        $date           = session('start');
        /** @var Carbon $end */
        $end            = session('end');
        $year           = $date->year;
        $page           = intval($request->get('page'));
        $pageSize       = intval(Preferences::get('transactionPageSize', 50)->data);
        $yearAverage    = $repository->getYearAverage($bill, $date);
        $overallAverage = $repository->getOverallAverage($bill);

        // use collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setBills(new Collection([$bill]))->setLimit($pageSize)->setPage($page)->withBudgetInformation()
                  ->withCategoryInformation();
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath(route('bills.show', [$bill->id]));


        $bill->paidDates = $repository->getPaidDatesInRange($bill, $date, $end);
        $bill->payDates  = $repository->getPayDatesInRange($bill, $date, $end);
        $lastPaidDate    = $this->lastPaidDate($repository->getPaidDatesInRange($bill, $date, $end), $date);
        if ($bill->paidDates->count() >= $bill->payDates->count()) {
            // if all bills have been been paid, jump to next period.
            $lastPaidDate = $end;
        }
        $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill, $lastPaidDate);
        $hideBill                = true;
        $subTitle                = e($bill->name);

        return view('bills.show', compact('transactions', 'yearAverage', 'overallAverage', 'year', 'hideBill', 'bill', 'subTitle'));
    }

    /**
     * @param BillFormRequest         $request
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BillFormRequest $request, BillRepositoryInterface $repository)
    {
        $billData = $request->getBillData();
        $bill     = $repository->store($billData);
        $request->session()->flash('success', strval(trans('firefly.stored_new_bill', ['name' => $bill->name])));
        Preferences::mark();


        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($bill, $files);

        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        if (1 === intval($request->get('create_another'))) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.create.fromStore', true);

            return redirect(route('bills.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('bills.create.uri'));
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

        $request->session()->flash('success', strval(trans('firefly.updated_bill', ['name' => $bill->name])));
        Preferences::mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($bill, $files);

        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        if (1 === intval($request->get('return_to_edit'))) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.edit.fromUpdate', true);

            return redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('bills.edit.uri'));
    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     *
     * @param Collection $dates
     * @param Carbon     $default
     *
     * @return Carbon
     */
    private function lastPaidDate(Collection $dates, Carbon $default): Carbon
    {
        if ($dates->count() === 0) {
            return $default;
        }
        $latest = $dates->first();
        /** @var Carbon $date */
        foreach ($dates as $date) {
            if ($date->gte($latest)) {
                $latest = $date;
            }
        }

        return $latest;

    }
}
