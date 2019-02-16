<?php
/**
 * TransactionController.php
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
/** @noinspection CallableParameterUseCaseInTypeContextInspection */
/** @noinspection MoreThanThreeArgumentsInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\CountAttachmentsFilter;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\SplitIndicatorFilter;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use View;

/**
 * Class TransactionController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionController extends Controller
{
    use ModelInformation, PeriodOverview;
    /** @var AttachmentRepositoryInterface */
    private $attachmentRepository;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');
                $this->repository           = app(JournalRepositoryInterface::class);
                $this->attachmentRepository = app(AttachmentRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Index for a range of transactions.
     *
     * @param Request     $request
     * @param string      $what
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, string $what, Carbon $start = null, Carbon $end = null)
    {
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        if (null === $start) {
            $start = session('start');
            $end   = session('end');
        }
        if (null === $end) {
            $end = session('end');
        }

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        $path = route('transactions.index', [$what, $start->format('Y-m-d'), $end->format('Y-m-d')]);

        $startStr = $start->formatLocalized($this->monthAndDayFormat);
        $endStr   = $end->formatLocalized($this->monthAndDayFormat);
        $subTitle = (string)trans('firefly.title_' . $what . '_between', ['start' => $startStr, 'end' => $endStr]);
        $periods  = $this->getTransactionPeriodOverview($what, $end);

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes($types)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->addFilter(SplitIndicatorFilter::class);
        $collector->addFilter(CountAttachmentsFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath($path);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'transactions', 'periods', 'start', 'end'));
    }

    /**
     * Index for ALL transactions.
     *
     * @param Request $request
     * @param string  $what
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAll(Request $request, string $what)
    {
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $path         = route('transactions.index.all', [$what]);
        $first        = $this->repository->firstNull();
        $start        = null === $first ? new Carbon : $first->date;
        $end          = new Carbon;
        $subTitle     = (string)trans('firefly.all_' . $what);

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes($types)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->addFilter(SplitIndicatorFilter::class);
        $collector->addFilter(CountAttachmentsFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath($path);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'transactions', 'start', 'end'));
    }

    /**
     * Do a reconciliation.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function reconcile(Request $request): JsonResponse
    {
        $transactionIds = $request->get('transactions');
        foreach ($transactionIds as $transactionId) {
            $transactionId = (int)$transactionId;
            $transaction   = $this->repository->findTransaction($transactionId);
            if (null !== $transaction) {
                Log::debug(sprintf('Transaction ID is %d', $transaction->id));
                $this->repository->reconcile($transaction);
            }
        }

        return response()->json(['ok' => 'reconciled']);
    }

    /**
     * Reorder transactions.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function reorder(Request $request): JsonResponse
    {
        $ids  = $request->get('items');
        $date = new Carbon($request->get('date'));
        if (\count($ids) > 0) {
            $order = 0;
            $ids   = array_unique($ids);
            foreach ($ids as $id) {
                $journal = $this->repository->findNull((int)$id);
                if (null !== $journal && $journal->date->isSameDay($date)) {
                    $this->repository->setOrder($journal, $order);
                    ++$order;
                }
            }
        }
        app('preferences')->mark();

        return response()->json([true]);
    }

    /**
     * Show a transaction.
     *
     * @param TransactionJournal          $journal
     * @param LinkTypeRepositoryInterface $linkTypeRepository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     * @throws FireflyException
     */
    public function show(TransactionJournal $journal, LinkTypeRepositoryInterface $linkTypeRepository)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        $transactionType = $journal->transactionType->type;
        if (TransactionType::RECONCILIATION === $transactionType) {
            return redirect(route('accounts.reconcile.show', [$journal->id])); // @codeCoverageIgnore
        }
        $linkTypes = $linkTypeRepository->get();
        $links     = $linkTypeRepository->getLinks($journal);

        // get attachments:
        $attachments = $this->repository->getAttachments($journal);
        $attachments = $attachments->each(
            function (Attachment $attachment) {
                $attachment->file_exists = $this->attachmentRepository->exists($attachment);

                return $attachment;
            }
        );

        // get transactions using the collector:
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));
        $set          = $collector->getTransactions();
        $transactions = [];

        /** @var TransactionTransformer $transformer */
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag);

        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $transactions[] = $transformer->transform($transaction);
        }

        $events   = $this->repository->getPiggyBankEvents($journal);
        $what     = strtolower($transactionType);
        $subTitle = trans('firefly.' . $what) . ' "' . $journal->description . '"';

        return view('transactions.show', compact('journal', 'attachments', 'events', 'subTitle', 'what', 'transactions', 'linkTypes', 'links'));
    }


}
