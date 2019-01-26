<?php
declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Chart;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class AvailableBudgetController
 */
class AvailableBudgetController extends Controller
{
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * AvailableBudgetController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param Request         $request
     *
     * @param AvailableBudget $availableBudget
     *
     * @return JsonResponse
     */
    public function overview(Request $request, AvailableBudget $availableBudget): JsonResponse
    {
        $currency          = $availableBudget->transactionCurrency;
        $budgets           = $this->repository->getActiveBudgets();
        $budgetInformation = $this->repository->spentInPeriodMc($budgets, new Collection, $availableBudget->start_date, $availableBudget->end_date);
        $spent             = 0.0;

        // get for current currency
        foreach ($budgetInformation as $spentInfo) {
            if ($spentInfo['currency_id'] === $availableBudget->transaction_currency_id) {
                $spent = $spentInfo['amount'];
            }
        }
        $left = bcadd($availableBudget->amount, (string)$spent);
        // left less than zero? Set to zero.
        if (bccomp($left, '0') === -1) {
            $left = '0';
        }

        $chartData = [
            [
                'label'                   => trans('firefly.spent'),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'pie',
                'yAxisID'                 => 0, // 0, 1, 2
                'fill'                    => null, // true, false, null
                'backgroundColor'         => null, // null or hex
                'entries'                 => [$spent * -1],
            ],
            [
                'label'                   => trans('firefly.left'),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'fill'                    => null, // true, false, null
                'backgroundColor'         => null, // null or hex
                'entries'                 => [round($left, $currency->decimal_places)],
            ],
        ];

        return response()->json($chartData);
    }

}