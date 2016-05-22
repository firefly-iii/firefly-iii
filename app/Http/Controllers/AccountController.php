<?php
/**
 * AccountController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
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
     * @return \Illuminate\View\View
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
     * @param AccountCrudInterface $crud
     * @param Account              $account
     *
     * @return View
     */
    public function delete(AccountCrudInterface $crud, Account $account)
    {
        $typeName    = config('firefly.shortNamesByFullName.' . $account->accountType->type);
        $subTitle    = trans('firefly.delete_' . $typeName . '_account', ['name' => $account->name]);
        $accountList = ExpandedForm::makeSelectListWithEmpty($crud->getAccountsByType([$account->accountType->type]));
        unset($accountList[$account->id]);

        // put previous url in session
        Session::put('accounts.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'delete-' . $typeName);

        return view('accounts.delete', compact('account', 'subTitle', 'accountList'));
    }

    /**
     * @param AccountCrudInterface $crud
     * @param Account              $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(AccountCrudInterface $crud, Account $account)
    {
        $type     = $account->accountType->type;
        $typeName = config('firefly.shortNamesByFullName.' . $type);
        $name     = $account->name;
        $moveTo   = $crud->find(intval(Input::get('move_account_before_delete')));

        $crud->destroy($account, $moveTo);

        Session::flash('success', strval(trans('firefly.' . $typeName . '_deleted', ['name' => $name])));
        Preferences::mark();

        return redirect(session('accounts.delete.url'));
    }

    /**
     * @param ARI     $repository
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function edit(ARI $repository, Account $account)
    {

        $what           = config('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle       = trans('firefly.edit_' . $what . '_account', ['name' => $account->name]);
        $subTitleIcon   = config('firefly.subIconsByIdentifier.' . $what);
        $openingBalance = $repository->openingBalanceTransaction($account);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('accounts.edit.fromUpdate') !== true) {
            Session::put('accounts.edit.url', URL::previous());
        }
        Session::forget('accounts.edit.fromUpdate');

        // pre fill some useful values.

        // the opening balance is tricky:
        $openingBalanceAmount = null;

        if ($openingBalance->id) {
            $transaction          = $repository->getFirstTransaction($openingBalance, $account);
            $openingBalanceAmount = $transaction->amount;
        }

        $preFilled = [
            'accountNumber'        => $account->getMeta('accountNumber'),
            'accountRole'          => $account->getMeta('accountRole'),
            'ccType'               => $account->getMeta('ccType'),
            'ccMonthlyPaymentDate' => $account->getMeta('ccMonthlyPaymentDate'),
            'openingBalanceDate'   => $openingBalance->id ? $openingBalance->date->format('Y-m-d') : null,
            'openingBalance'       => $openingBalanceAmount,
            'virtualBalance'       => round($account->virtual_balance, 2),
        ];
        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'edit-' . $what);

        return view('accounts.edit', compact('account', 'subTitle', 'subTitleIcon', 'openingBalance', 'what'));
    }

    /**
     * @param AccountCrudInterface $crud
     * @param string               $what
     *
     * @return View
     */
    public function index(AccountCrudInterface $crud, string $what)
    {
        $what = $what ?? 'asset';

        $subTitle     = trans('firefly.' . $what . '_accounts');
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        $types        = config('firefly.accountTypesByIdentifier.' . $what);
        $accounts     = $crud->getAccountsByType($types);
        $start        = clone session('start', Carbon::now()->startOfMonth());
        $end          = clone session('end', Carbon::now()->endOfMonth());
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
     * @param ARI     $repository
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function show(ARI $repository, Account $account)
    {
        // show journals from current period only:
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $subTitle     = $account->name;
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = session('start', Navigation::startOfPeriod(new Carbon, $range));
        $end          = session('end', Navigation::endOfPeriod(new Carbon, $range));
        $page         = intval(Input::get('page'));
        $pageSize     = Preferences::get('transactionPageSize', 50)->data;
        $offset       = ($page - 1) * $pageSize;
        $set          = $repository->journalsInPeriod(new Collection([$account]), [], $start, $end);
        $count        = $set->count();
        $subSet       = $set->splice($offset, $pageSize);
        $journals     = new LengthAwarePaginator($subSet, $count, $pageSize, $page);
        $journals->setPath('accounts/show/' . $account->id);

        // grouped other months thing:
        // oldest transaction in account:
        $start = $repository->firstUseDate($account);
        if ($start->year == 1900) {
            $start = new Carbon;
        }
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

        while ($end >= $start) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);
            $spent      = $this->spentInPeriod($account, $end, $currentEnd);
            $earned     = $this->earnedInPeriod($account, $end, $currentEnd);
            $dateStr    = $end->format('Y-m-d');
            $dateName   = Navigation::periodShow($end, $range);
            $entries->push([$dateStr, $dateName, $spent, $earned]);
            $end = Navigation::subtractPeriod($end, $range, 1);

        }

        return view('accounts.show', compact('account', 'what', 'entries', 'subTitleIcon', 'journals', 'subTitle'));
    }

    /**
     * @param ARI     $repository
     * @param Account $account
     * @param string  $date
     *
     * @return View
     */
    public function showWithDate(ARI $repository, Account $account, string $date)
    {
        $carbon   = new Carbon($date);
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = Navigation::startOfPeriod($carbon, $range);
        $end      = Navigation::endOfPeriod($carbon, $range);
        $subTitle = $account->name . ' (' . Navigation::periodShow($start, $range) . ')';
        $page     = intval(Input::get('page'));
        $pageSize = Preferences::get('transactionPageSize', 50)->data;
        $offset   = ($page - 1) * $pageSize;
        $set      = $repository->journalsInPeriod(new Collection([$account]), [], $start, $end);
        $count    = $set->count();
        $subSet   = $set->splice($offset, $pageSize);
        $journals = new LengthAwarePaginator($subSet, $count, $pageSize, $page);
        $journals->setPath('accounts/show/' . $account->id . '/' . $date);

        return view('accounts.show_with_date', compact('category', 'date', 'account', 'journals', 'subTitle', 'carbon'));
    }

    /**
     * @param AccountFormRequest   $request
     * @param AccountCrudInterface $crud
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(AccountFormRequest $request, AccountCrudInterface $crud)
    {
        $accountData = [
            'name'                   => $request->input('name'),
            'accountType'            => $request->input('what'),
            'virtualBalance'         => round($request->input('virtualBalance'), 2),
            'virtualBalanceCurrency' => intval($request->input('amount_currency_id_virtualBalance')),
            'active'                 => true,
            'user'                   => Auth::user()->id,
            'iban'                   => $request->input('iban'),
            'accountNumber'          => $request->input('accountNumber'),
            'accountRole'            => $request->input('accountRole'),
            'openingBalance'         => round($request->input('openingBalance'), 2),
            'openingBalanceDate'     => new Carbon((string)$request->input('openingBalanceDate')),
            'openingBalanceCurrency' => intval($request->input('amount_currency_id_openingBalance')),

        ];

        $account = $crud->store($accountData);

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
     * @param AccountFormRequest   $request
     * @param AccountCrudInterface $crud
     * @param Account              $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(AccountFormRequest $request, AccountCrudInterface $crud, Account $account)
    {

        $accountData = [
            'name'                   => $request->input('name'),
            'active'                 => $request->input('active'),
            'user'                   => Auth::user()->id,
            'iban'                   => $request->input('iban'),
            'accountNumber'          => $request->input('accountNumber'),
            'accountRole'            => $request->input('accountRole'),
            'virtualBalance'         => round($request->input('virtualBalance'), 2),
            'openingBalance'         => round($request->input('openingBalance'), 2),
            'openingBalanceDate'     => new Carbon((string)$request->input('openingBalanceDate')),
            'openingBalanceCurrency' => intval($request->input('amount_currency_id_openingBalance')),
            'ccType'                 => $request->input('ccType'),
            'ccMonthlyPaymentDate'   => $request->input('ccMonthlyPaymentDate'),
        ];
        $crud->update($account, $accountData);

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

    /**
     * Asset accounts actually earn money by being the destination of a deposit or the destination
     * of a transfer. The money moves to them.
     *
     * A revenue account doesn't really earn money itself. Money is earned "from" the revenue account.
     * So, the call to find out how many money has been earned by/from a revenue account is slightly different.
     *
     *
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return string
     */
    private function earnedInPeriod(Account $account, Carbon $start, Carbon $end)
    {
        /** @var ARI $repository */
        $repository = app(ARI::class);
        $collection = new Collection([$account]);
        $type       = $account->accountType->type;
        switch ($type) {
            case AccountType::DEFAULT:
            case AccountType::ASSET:
                return $repository->earnedInPeriod($collection, $start, $end);
            case AccountType::REVENUE:
                return $repository->earnedFromInPeriod($collection, $start, $end);
            default:
                return '0';
        }
    }

    /**
     * Asset accounts actually spend money by being the source of a withdrawal or the source
     * of a transfer. The money moves away from them.
     *
     * An expense account doesn't really spend money itself. Money is spent "at" the expense account.
     * So, the call to find out how many money has been spent on/at an expense account is slightly different.
     *
     *
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return string
     */
    private function spentInPeriod(Account $account, Carbon $start, Carbon $end): string
    {
        /** @var ARI $repository */
        $repository = app(ARI::class);
        $collection = new Collection([$account]);
        $type       = $account->accountType->type;
        switch ($type) {
            case AccountType::DEFAULT:
            case AccountType::ASSET:
                return $repository->spentInPeriod($collection, $start, $end);
            case AccountType::EXPENSE:
                return $repository->spentAtInPeriod($collection, $start, $end);
            default:
                return '0';
        }
    }

}
