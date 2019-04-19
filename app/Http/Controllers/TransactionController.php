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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\CountAttachmentsFilter;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\SplitIndicatorFilter;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

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
     * Index for ALL transactions.
     *
     * @param Request $request
     * @param string  $what
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAll(Request $request, string $what)
    {
        throw new FireflyException('Do not use me.');
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


}
