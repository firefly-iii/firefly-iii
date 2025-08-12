<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Chart;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Chart\ChartRequest;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Chart\ChartData;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\AccountBalanceGrouped;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\CollectsAccountsFromFilter;
use Illuminate\Http\JsonResponse;

/**
 * Class BalanceController
 */
class BalanceController extends Controller
{
    use CleansChartData;
    use CollectsAccountsFromFilter;

    private ChartData                  $chartData;
    private GroupCollectorInterface    $collector;
    private AccountRepositoryInterface $repository;

    // private TransactionCurrency        $default;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->collector  = app(GroupCollectorInterface::class);
                $userGroup        = $this->validateUserGroup($request);
                $this->repository->setUserGroup($userGroup);
                $this->collector->setUserGroup($userGroup);
                $this->chartData  = new ChartData();
                // $this->default    = app('amount')->getPrimaryCurrency();

                return $next($request);
            }
        );
    }

    /**
     * The code is practically a duplicate of ReportController::operations.
     *
     * Currency is up to the account/transactions in question, but conversion to the default
     * currency is possible.
     *
     * If the transaction being processed is already in native currency OR if the
     * foreign amount is in the native currency, the amount will not be converted.
     *
     * @throws FireflyException
     */
    public function balance(ChartRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $accounts        = $this->getAccountList($queryParameters);

        // prepare for currency conversion and data collection:
        /** @var TransactionCurrency $primary */
        $primary         = Amount::getPrimaryCurrency();

        // get journals for entire period:

        $this->collector->setRange($queryParameters['start'], $queryParameters['end'])
            ->withAccountInformation()
            ->setXorAccounts($accounts)
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::RECONCILIATION->value, TransactionTypeEnum::TRANSFER->value])
        ;
        $journals        = $this->collector->getExtractedJournals();

        $object          = new AccountBalanceGrouped();
        $object->setPreferredRange($queryParameters['period']);
        $object->setPrimary($primary);
        $object->setAccounts($accounts);
        $object->setJournals($journals);
        $object->setStart($queryParameters['start']);
        $object->setEnd($queryParameters['end']);
        $object->groupByCurrencyAndPeriod();
        $data            = $object->convertToChartData();
        foreach ($data as $entry) {
            $this->chartData->add($entry);
        }

        return response()->json($this->chartData->render());
    }
}
