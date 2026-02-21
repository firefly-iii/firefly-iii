<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Generic\PaginationDateRangeRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CreditCard\StatementPeriod;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

class StatementController extends Controller
{
    use TransactionFilter;

    public const string RESOURCE_KEY = 'accounts';

    private AccountRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->repository = app(AccountRepositoryInterface::class);
            $this->repository->setUser(auth()->user());

            return $next($request);
        });
    }

    public function show(PaginationDateRangeRequest $request, Account $account): JsonResponse
    {
        $accountRole = $this->repository->getMetaValue($account, 'account_role');
        if ('ccAsset' !== $accountRole) {
            return response()->json(['message' => 'Account is not a credit card.', 'exception' => 'NotFoundHttpException'], 404);
        }

        $closingDay = (int) $this->repository->getMetaValue($account, 'cc_closing_day');
        if (0 === $closingDay) {
            return response()->json(['message' => 'No closing day configured for this credit card.', 'exception' => 'NotFoundHttpException'], 404);
        }

        $paymentDateStr = $this->repository->getMetaValue($account, 'cc_monthly_payment_date');
        $dueDay         = null;
        if ('' !== $paymentDateStr) {
            $parsed = Carbon::parse($paymentDateStr, config('app.timezone'));
            $dueDay = $parsed->day;
        }

        ['limit' => $limit, 'page' => $page, 'types' => $types] = $request->attributes->all();

        $dateParam = $request->get('date');
        $refDate   = null !== $dateParam && '' !== $dateParam
            ? Carbon::parse($dateParam, config('app.timezone'))
            : today(config('app.timezone'));

        $period  = StatementPeriod::forDate($closingDay, $refDate, $dueDay);
        $manager = $this->getManager();

        /** @var User $admin */
        $admin     = auth()->user();

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            ->setAccounts(new Collection()->push($account))
            ->withAPIInformation()
            ->setLimit($limit)
            ->setPage($page)
            ->setTypes($types)
            ->setRange($period->start, $period->end)
        ;

        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.accounts.statements', [$account->id]) . $this->buildParams());

        $enrichment = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $transactions = $enrichment->enrich($paginator->getCollection());

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        /** @var GroupCollectorInterface $totalsCollector */
        $totalsCollector = app(GroupCollectorInterface::class);
        $totalsCollector
            ->setUser($admin)
            ->setAccounts(new Collection()->push($account))
            ->setTypes($types)
            ->setRange($period->start, $period->end)
        ;
        $allGroups     = $totalsCollector->getGroups();
        $totalCharges  = '0';
        $totalPayments = '0';
        $accountId     = $account->id;
        foreach ($allGroups as $group) {
            foreach ($group['transactions'] ?? [] as $tx) {
                $amount = (string) ($tx['amount'] ?? '0');
                if ((int) ($tx['destination_account_id'] ?? 0) === $accountId) {
                    $amount = bcmul($amount, '-1', 2);
                }
                if (bccomp($amount, '0', 2) < 0) {
                    $totalCharges = bcadd($totalCharges, $amount, 2);
                } else {
                    $totalPayments = bcadd($totalPayments, $amount, 2);
                }
            }
        }

        $result              = $manager->createData($resource)->toArray();
        $result['statement'] = [
            'start'          => $period->start->format('Y-m-d'),
            'end'            => $period->end->format('Y-m-d'),
            'closing_day'    => $closingDay,
            'due_date'       => $period->dueDate?->format('Y-m-d'),
            'total_charges'  => $totalCharges,
            'total_payments' => $totalPayments,
            'balance'        => bcadd($totalCharges, $totalPayments, 2),
        ];

        return response()->json($result)->header('Content-Type', self::CONTENT_TYPE);
    }
}
