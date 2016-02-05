<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Carbon\Carbon;
use Config;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Input;
use Preferences;
use Response;

/**
 * Class JsonController
 *
 * @package FireflyIII\Http\Controllers
 */
class JsonController extends Controller
{
    /**
     * JsonController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function action()
    {
        $count   = intval(Input::get('count')) > 0 ? intval(Input::get('count')) : 1;
        $keys    = array_keys(Config::get('firefly.rule-actions'));
        $actions = [];
        foreach ($keys as $key) {
            $actions[$key] = trans('firefly.rule_action_' . $key . '_choice');
        }
        $view = view('rules.partials.action', compact('actions', 'count'))->render();


        return Response::json(['html' => $view]);
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxBillsPaid(BillRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        bcscale(2);

        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $amount        = $repository->getBillsPaidInRange($start, $end); // will be a negative amount.
        $creditCardDue = $repository->getCreditCardBill($start, $end);
        if ($creditCardDue >= 0) {
            $amount = bcadd($amount, $creditCardDue);
        }
        $amount = bcmul($amount, '-1');

        $data = ['box' => 'bills-paid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];

        return Response::json($data);
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function boxBillsUnpaid(BillRepositoryInterface $repository)
    {
        bcscale(2);
        $start         = session('start', Carbon::now()->startOfMonth());
        $end           = session('end', Carbon::now()->endOfMonth());
        $amount        = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $creditCardDue = $repository->getCreditCardBill($start, $end);

        if ($creditCardDue < 0) {
            // expenses are negative (bill not yet paid),
            $creditCardDue = bcmul($creditCardDue, '-1');
            $amount        = bcadd($amount, $creditCardDue);
        }

        $data = ['box' => 'bills-unpaid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];

        return Response::json($data);
    }

    /**
     * @param ReportQueryInterface $reportQuery
     *
     * @param ARI                  $accountRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxIn(ReportQueryInterface $reportQuery, ARI $accountRepository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        // works for json too!
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-in');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $accounts = $accountRepository->getAccounts(['Default account', 'Asset account', 'Cash account']);
        $amount   = $reportQuery->income($accounts, $start, $end)->sum('journalAmount');

        $data = ['box' => 'in', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param ReportQueryInterface $reportQuery
     *
     * @param ARI                  $accountRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxOut(ReportQueryInterface $reportQuery, ARI $accountRepository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        $accounts = $accountRepository->getAccounts(['Default account', 'Asset account', 'Cash account']);

        // works for json too!
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-out');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $amount = $reportQuery->expense($accounts, $start, $end)->sum('journalAmount');

        $data = ['box' => 'out', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Returns a list of categories.
     *
     * @param CRI $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(CRI $repository)
    {
        $list   = $repository->listCategories();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function endTour()
    {
        Preferences::set('tour', false);

        return Response::json('true');
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param ARI $accountRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts(ARI $accountRepository)
    {
        $list   = $accountRepository->getAccounts(['Expense account', 'Beneficiary account']);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @param ARI $accountRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts(ARI $accountRepository)
    {
        $list   = $accountRepository->getAccounts(['Revenue account']);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param TagRepositoryInterface $tagRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(TagRepositoryInterface $tagRepository)
    {
        $list   = $tagRepository->get();
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->tag;
        }

        return Response::json($return);

    }

    /**
     *
     */
    public function tour()
    {
        $pref = Preferences::get('tour', true);
        if (!$pref) {
            abort(404);
        }
        $headers = ['main-content', 'sidebar-toggle', 'account-menu', 'budget-menu', 'report-menu', 'transaction-menu', 'option-menu', 'main-content-end'];
        $steps   = [];
        foreach ($headers as $header) {
            $steps[] = [
                'element' => '#' . $header,
                'title'   => trans('help.' . $header . '-title'),
                'content' => trans('help.' . $header . '-text'),
            ];
        }
        $steps[0]['orphan']    = true;// orphan and backdrop for first element.
        $steps[0]['backdrop']  = true;
        $steps[1]['placement'] = 'left';// sidebar position left:
        $steps[7]['orphan']    = true; // final in the center again.
        $steps[7]['backdrop']  = true;
        $template              = view('json.tour')->render();

        return Response::json(['steps' => $steps, 'template' => $template]);
    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transactionJournals(JournalRepositoryInterface $repository, $what)
    {
        $descriptions = [];
        $dbType       = $repository->getTransactionType($what);

        $journals = $repository->getJournalsOfType($dbType);
        foreach ($journals as $j) {
            $descriptions[] = $j->description;
        }

        $descriptions = array_unique($descriptions);
        sort($descriptions);

        return Response::json($descriptions);


    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function trigger()
    {
        $count    = intval(Input::get('count')) > 0 ? intval(Input::get('count')) : 1;
        $keys     = array_keys(Config::get('firefly.rule-triggers'));
        $triggers = [];
        foreach ($keys as $key) {
            if ($key != 'user_action') {
                $triggers[$key] = trans('firefly.rule_trigger_' . $key . '_choice');
            }
        }

        $view = view('rules.partials.trigger', compact('triggers', 'count'))->render();


        return Response::json(['html' => $view]);
    }

}
