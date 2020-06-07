<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\PiggyBank;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-bullseye');

                $this->piggyRepos = app(PiggyBankRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show overview of all piggy banks.
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function index(Request $request)
    {
        $this->piggyRepos->correctOrder();
        $collection = $this->piggyRepos->getPiggyBanks();
        $accounts   = [];
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());

        // transform piggies using the transformer:
        $parameters = new ParameterBag;
        $parameters->set('end', $end);

        // make piggy bank groups:
        $piggyBanks = [
            0 => [ // the index is the order, not the ID.
                   'object_group_id'    => 0,
                   'object_group_title' => (string) trans('firefly.default_group_title_name'),
                   'piggy_banks'        => [],
            ],
        ];

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters(new ParameterBag);

        /** @var AccountTransformer $accountTransformer */
        $accountTransformer = app(AccountTransformer::class);
        $accountTransformer->setParameters($parameters);
        /** @var PiggyBank $piggy */
        foreach ($collection as $piggy) {
            $array      = $transformer->transform($piggy);
            $groupOrder = $array['object_group_order'];
            // make group array if necessary:
            $piggyBanks[$groupOrder] = $piggyBanks[$groupOrder] ?? [
                    'object_group_id'    => $array['object_group_id'],
                    'object_group_title' => $array['object_group_title'],
                    'piggy_banks'        => [],
                ];

            $account              = $accountTransformer->transform($piggy->account);
            $accountId            = (int) $account['id'];
            $array['attachments'] = $this->piggyRepos->getAttachments($piggy);
            if (!isset($accounts[$accountId])) {
                // create new:
                $accounts[$accountId] = $account;

                // add some interesting details:
                $accounts[$accountId]['left']    = $accounts[$accountId]['current_balance'];
                $accounts[$accountId]['saved']   = 0;
                $accounts[$accountId]['target']  = 0;
                $accounts[$accountId]['to_save'] = 0;
            }

            // calculate new interesting fields:
            $accounts[$accountId]['left']    -= $array['current_amount'];
            $accounts[$accountId]['saved']   += $array['current_amount'];
            $accounts[$accountId]['target']  += $array['target_amount'];
            $accounts[$accountId]['to_save'] += ($array['target_amount'] - $array['current_amount']);
            $array['account_name']           = $account['name'];
            $piggyBanks[$groupOrder]['piggy_banks'][] = $array;
        }

        return view('piggy-banks.index', compact('piggyBanks', 'accounts'));
    }

    /**
     * Set the order of a piggy bank.
     *
     * @param Request   $request
     * @param PiggyBank $piggyBank
     *
     * @return JsonResponse
     */
    public function setOrder(Request $request, PiggyBank $piggyBank): JsonResponse
    {
        $newOrder = (int) $request->get('order');
        $this->piggyRepos->setOrder($piggyBank, $newOrder);

        return response()->json(['data' => 'OK']);
    }
}
