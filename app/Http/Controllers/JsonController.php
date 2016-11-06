<?php
/**
 * JsonController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Http\Controllers;

use Amount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
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
        $keys    = array_keys(config('firefly.rule-actions'));
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

        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $amount = $repository->getBillsPaidInRange($start, $end); // will be a negative amount.
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
        $start  = session('start', Carbon::now()->startOfMonth());
        $end    = session('end', Carbon::now()->endOfMonth());
        $amount = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $data   = ['box' => 'bills-unpaid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];

        return Response::json($data);
    }

    /**
     * @param AccountTaskerInterface     $accountTasker
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @internal param ARI $accountRepository
     */
    public function boxIn(AccountTaskerInterface $accountTasker, AccountRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        // works for json too!
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-in');
        if ($cache->has()) {
            return Response::json($cache->get());
        }
        $accounts = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $assets   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $amount   = $accountTasker->amountInInPeriod($accounts, $assets, $start, $end);
        $data     = ['box' => 'in', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param AccountTaskerInterface     $accountTasker
     * @param AccountRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxOut(AccountTaskerInterface $accountTasker, AccountRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        // works for json too!
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-out');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $accounts = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $assets   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $amount   = $accountTasker->amountOutInPeriod($accounts, $assets, $start, $end);

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
        $list   = $repository->getCategories();
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
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function expenseAccounts(AccountRepositoryInterface $repository)
    {
        $list   = $repository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function revenueAccounts(AccountRepositoryInterface $repository)
    {
        $list   = $repository->getAccountsByType([AccountType::REVENUE]);
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
            throw new FireflyException('Cannot find preference for tour. Exit.');
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
     * @param $what
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionJournals($what)
    {
        $descriptions = [];
        $type         = config('firefly.transactionTypesByWhat.' . $what);
        $types        = [$type];

        // use journal collector instead:
        $collector = new JournalCollector(auth()->user());
        $collector->setTypes($types)->setLimit(100)->setPage(1);
        $journals = $collector->getJournals();
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
        $keys     = array_keys(config('firefly.rule-triggers'));
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
