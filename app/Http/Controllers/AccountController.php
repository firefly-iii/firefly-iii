<?php
/**
 * AccountController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Input;
use Navigation;
use Preferences;
use Session;
use Steam;
use URL;
use View;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers
 */
class AccountController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('mainTitleIcon', 'fa-credit-card');
        View::share('title', trans('firefly.accounts'));
    }

    /**
     * @param string $what
     *
     * @return View
     */
    public function create(string $what = 'asset')
    {
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        $subTitle     = trans('firefly.make_new_' . $what . '_account');
        Session::flash('preFilled', []);

        // put previous url in session if not redirect from store (not "create another").
        if (session('accounts.create.fromStore') !== true) {
            Session::put('accounts.create.url', URL::previous());
        }
        Session::forget('accounts.create.fromStore');
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'create-' . $what);

        return view('accounts.create', compact('subTitleIcon', 'what', 'subTitle'));

    }

    /**
     * @param ARI     $repository
     * @param Account $account
     *
     * @return View
     */
    public function delete(ARI $repository, Account $account)
    {
        $typeName    = config('firefly.shortNamesByFullName.' . $account->accountType->type);
        $subTitle    = trans('firefly.delete_' . $typeName . '_account', ['name' => $account->name]);
        $accountList = ExpandedForm::makeSelectListWithEmpty($repository->getAccountsByType([$account->accountType->type]));
        unset($accountList[$account->id]);

        // put previous url in session
        Session::put('accounts.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'delete-' . $typeName);

        return view('accounts.delete', compact('account', 'subTitle', 'accountList'));
    }

    /**
     * @param ARI     $repository
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(ARI $repository, Account $account)
    {
        $type     = $account->accountType->type;
        $typeName = config('firefly.shortNamesByFullName.' . $type);
        $name     = $account->name;
        $moveTo   = $repository->find(intval(Input::get('move_account_before_delete')));

        $repository->destroy($account, $moveTo);

        Session::flash('success', strval(trans('firefly.' . $typeName . '_deleted', ['name' => $name])));
        Preferences::mark();

        return redirect(session('accounts.delete.url'));
    }

    /**
     * @param Account $account
     *
     * @return View
     */
    public function edit(Account $account)
    {

        $what         = config('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle     = trans('firefly.edit_' . $what . '_account', ['name' => $account->name]);
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('accounts.edit.fromUpdate') !== true) {
            Session::put('accounts.edit.url', URL::previous());
        }
        Session::forget('accounts.edit.fromUpdate');

        // pre fill some useful values.

        // the opening balance is tricky:
        $openingBalanceAmount = $account->getOpeningBalanceAmount();
        $openingBalanceAmount = $account->getOpeningBalanceAmount() === '0' ? '' : $openingBalanceAmount;
        $openingBalanceDate   = $account->getOpeningBalanceDate();
        $openingBalanceDate   = $openingBalanceDate->year === 1900 ? null : $openingBalanceDate->format('Y-m-d');

        $preFilled = [
            'accountNumber'        => $account->getMeta('accountNumber'),
            'accountRole'          => $account->getMeta('accountRole'),
            'ccType'               => $account->getMeta('ccType'),
            'ccMonthlyPaymentDate' => $account->getMeta('ccMonthlyPaymentDate'),
            'openingBalanceDate'   => $openingBalanceDate,
            'openingBalance'       => $openingBalanceAmount,
            'virtualBalance'       => round($account->virtual_balance, 2),
        ];
        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'edit-' . $what);

        return view('accounts.edit', compact('account', 'subTitle', 'subTitleIcon', 'openingBalance', 'what'));
    }

    /**
     * @param ARI    $repository
     * @param string $what
     *
     * @return View
     */
    public function index(ARI $repository, string $what)
    {
        $what = $what ?? 'asset';

        $subTitle     = trans('firefly.' . $what . '_accounts');
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        $types        = config('firefly.accountTypesByIdentifier.' . $what);
        $accounts     = $repository->getAccountsByType($types);
        /** @var Carbon $start */
        $start = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end = clone session('end', Carbon::now()->endOfMonth());
        $start->subDay();

        $ids           = $accounts->pluck('id')->toArray();
        $startBalances = Steam::balancesById($ids, $start);
        $endBalances   = Steam::balancesById($ids, $end);
        $activities    = Steam::getLastActivities($ids);

        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances) {
                $account->lastActivityDate = $this->isInArray($activities, $account->id);
                $account->startBalance     = $this->isInArray($startBalances, $account->id);
                $account->endBalance       = $this->isInArray($endBalances, $account->id);
            }
        );

        return view('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
    }

    /**
     * @param AccountTaskerInterface $tasker
     * @param ARI                    $repository
     * @param Account                $account
     *
     * @return View
     */
    public function show(AccountTaskerInterface $tasker, ARI $repository, Account $account)
    {
        // show journals from current period only:
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $subTitle     = $account->name;
        $range        = Preferences::get('viewRange', '1M')->data;
        /** @var Carbon $start */
        $start = session('start', Navigation::startOfPeriod(new Carbon, $range));
        /** @var Carbon $end */
        $end      = session('end', Navigation::endOfPeriod(new Carbon, $range));
        $page     = intval(Input::get('page'));
        $pageSize = Preferences::get('transactionPageSize', 50)->data;
        $offset   = ($page - 1) * $pageSize;
        $set      = $tasker->getJournalsInPeriod(new Collection([$account]), [], $start, $end);
        $count    = $set->count();
        $subSet   = $set->splice($offset, $pageSize);
        $journals = new LengthAwarePaginator($subSet, $count, $pageSize, $page);
        $journals->setPath('accounts/show/' . $account->id);

        // grouped other months thing:
        // oldest transaction in account:
        $start   = $repository->oldestJournalDate($account);
        $range   = Preferences::get('viewRange', '1M')->data;
        $start   = Navigation::startOfPeriod($start, $range);
        $end     = Navigation::endOfX(new Carbon, $range);
        $entries = new Collection;

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-show');
        $cache->addProperty($account->id);


        if ($cache->has()) {
            $entries = $cache->get();

            return view('accounts.show', compact('account', 'what', 'entries', 'subTitleIcon', 'journals', 'subTitle'));
        }

        // only include asset accounts when this account is an asset:
        $assets = new Collection;
        if (in_array($account->accountType->type, [AccountType::ASSET, AccountType::DEFAULT])) {
            $assets = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        }

        while ($end >= $start) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);
            $spent      = $tasker->amountOutInPeriod(new Collection([$account]), $assets, $end, $currentEnd);
            $earned     = $tasker->amountInInPeriod(new Collection([$account]), $assets, $end, $currentEnd);
            $dateStr    = $end->format('Y-m-d');
            $dateName   = Navigation::periodShow($end, $range);
            $entries->push([$dateStr, $dateName, $spent, $earned]);
            $end = Navigation::subtractPeriod($end, $range, 1);

        }
        $cache->store($entries);

        return view('accounts.show', compact('account', 'what', 'entries', 'subTitleIcon', 'journals', 'subTitle'));
    }

    /**
     * @param AccountTaskerInterface $tasker
     * @param Account                $account
     * @param string                 $date
     *
     * @return View
     */
    public function showWithDate(AccountTaskerInterface $tasker, Account $account, string $date)
    {
        $carbon   = new Carbon($date);
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = Navigation::startOfPeriod($carbon, $range);
        $end      = Navigation::endOfPeriod($carbon, $range);
        $subTitle = $account->name . ' (' . Navigation::periodShow($start, $range) . ')';
        $page     = intval(Input::get('page'));
        $page     = $page === 0 ? 1 : $page;
        $pageSize = Preferences::get('transactionPageSize', 50)->data;
        $offset   = ($page - 1) * $pageSize;
        $set      = $tasker->getJournalsInPeriod(new Collection([$account]), [], $start, $end);
        $count    = $set->count();
        $subSet   = $set->splice($offset, $pageSize);
        $journals = new LengthAwarePaginator($subSet, $count, $pageSize, $page);
        $journals->setPath('accounts/show/' . $account->id . '/' . $date);

        return view('accounts.show_with_date', compact('category', 'date', 'account', 'journals', 'subTitle', 'carbon'));
    }

    /**
     * @param AccountFormRequest $request
     * @param ARI                $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     */
    public function store(AccountFormRequest $request, ARI $repository)
    {
        $data    = $request->getAccountData();
        $account = $repository->store($data);

        Session::flash('success', strval(trans('firefly.stored_new_account', ['name' => $account->name])));
        Preferences::mark();

        // update preferences if necessary:
        $frontPage = Preferences::get('frontPageAccounts', [])->data;
        if (count($frontPage) > 0) {
            $frontPage[] = $account->id;
            Preferences::set('frontPageAccounts', $frontPage);
        }

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('accounts.create.fromStore', true);

            return redirect(route('accounts.create', [$request->input('what')]))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('accounts.create.url'));
    }

    /**
     * @param AccountFormRequest $request
     * @param ARI                $repository
     * @param Account            $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(AccountFormRequest $request, ARI $repository, Account $account)
    {
        $data    = $request->getAccountData();
        $repository->update($account, $data);

        Session::flash('success', strval(trans('firefly.updated_account', ['name' => $account->name])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('accounts.edit.fromUpdate', true);

            return redirect(route('accounts.edit', [$account->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('accounts.edit.url'));

    }


    /**
     * @param array $array
     * @param int   $entryId
     *
     * @return null|mixed
     */
    protected function isInArray(array $array, int $entryId)
    {
        if (isset($array[$entryId])) {
            return $array[$entryId];
        }

        return '';
    }
}
