<?php
declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function overview(Request $request): JsonResponse
    {
        // parameters for chart:
        $start = (string)$request->get('start');
        $end   = (string)$request->get('end');
        if ('' === $start || '' === $end) {
            throw new FireflyException('Start and end are mandatory parameters.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $start);
        $end   = Carbon::createFromFormat('Y-m-d', $end);

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray();
        $frontPage  = app('preferences')->get('frontPageAccounts', $defaultSet);
        $default    = app('amount')->getDefaultCurrency();
        if (0 === \count($frontPage->data)) {
            $frontPage->data = $defaultSet;
            $frontPage->save();
        }

        // get accounts:
        $accounts  = $this->repository->getAccountsById($frontPage->data);
        $chartData = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet = [
                'label'                   => $account->name,
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'fill'                    => null, // true, false, null
                'backgroundColor'         => null, // null or hex
                'entries'                 => [],
            ];

            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = round(array_values($range)[0], 12);
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->formatLocalized((string)trans('config.month_and_day'));
                $balance  = isset($range[$format]) ? round($range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[] = $currentSet;
        }

        return response()->json($chartData);
    }

}