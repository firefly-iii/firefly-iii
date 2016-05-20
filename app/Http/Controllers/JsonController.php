<?php
/**
 * JsonController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Http\Controllers;

use Amount;
use Carbon\Carbon;
use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
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
     * @param ARI                  $accountRepository
     * @param AccountCrudInterface $crud
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function boxIn(ARI $accountRepository, AccountCrudInterface $crud)
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
        $accounts = $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $amount   = $accountRepository->earnedInPeriod($accounts, $start, $end);
        $data     = ['box' => 'in', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param ARI                  $accountRepository
     * @param AccountCrudInterface $crud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxOut(ARI $accountRepository, AccountCrudInterface $crud)
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

        $accounts = $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $amount   = $accountRepository->spentInPeriod($accounts, $start, $end);

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
     * @param AccountCrudInterface $crud
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts(AccountCrudInterface $crud)
    {
        $list   = $crud->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $return = [];
        foreach ($list as $entry) {
            $return[] = $entry->name;
        }

        return Response::json($return);

    }

    /**
     * @param AccountCrudInterface $crud
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueAccounts(AccountCrudInterface $crud)
    {
        $list   = $crud->getAccountsByType([AccountType::REVENUE]);
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
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transactionJournals(JournalRepositoryInterface $repository, $what)
    {
        $descriptions = [];
        $type         = config('firefly.transactionTypesByWhat.' . $what);
        $types        = [$type];
        $journals     = $repository->getJournals($types, 1, 50);
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
