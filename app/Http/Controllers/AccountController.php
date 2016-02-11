<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use ExpandedForm;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use Input;
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


        $subTitleIcon = Config::get('firefly.subIconsByIdentifier.' . $what);
        $subTitle     = trans('firefly.make_new_' . $what . '_account');

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
     * @return \Illuminate\View\View
     */
    public function delete(ARI $repository, Account $account)
    {
        $typeName    = Config::get('firefly.shortNamesByFullName.' . $account->accountType->type);
        $subTitle    = trans('firefly.delete_' . $typeName . '_account', ['name' => $account->name]);
        $accountList = ExpandedForm::makeSelectList($repository->getAccounts([$account->accountType->type]), true);
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ARI $repository, Account $account)
    {
        $type     = $account->accountType->type;
        $typeName = Config::get('firefly.shortNamesByFullName.' . $type);
        $name     = $account->name;
        $moveTo   = Auth::user()->accounts()->find(intval(Input::get('move_account_before_delete')));

        $repository->destroy($account, $moveTo);

        Session::flash('success', trans('firefly.' . $typeName . '_deleted', ['name' => $name]));
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

        $what           = Config::get('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle       = trans('firefly.edit_' . $what . '_account', ['name' => $account->name]);
        $subTitleIcon   = Config::get('firefly.subIconsByIdentifier.' . $what);
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
     * @param ARI                        $repository
     * @param                            $what
     *
     * @return \Illuminate\View\View
     */
    public function index(ARI $repository, string $what)
    {
        $subTitle     = trans('firefly.' . $what . '_accounts');
        $subTitleIcon = Config::get('firefly.subIconsByIdentifier.' . $what);
        $types        = Config::get('firefly.accountTypesByIdentifier.' . $what);
        $accounts     = $repository->getAccounts($types);
        /** @var Carbon $start */
        $start        = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
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
        $page         = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $subTitleIcon = Config::get('firefly.subTitlesByIdentifier.' . $account->accountType->type);
        $what         = Config::get('firefly.shortNamesByFullName.' . $account->accountType->type);
        $journals     = $repository->getJournals($account, $page);
        $subTitle     = trans('firefly.details_for_' . $what, ['name' => $account->name]);
        $journals->setPath('accounts/show/' . $account->id);


        return view('accounts.show', compact('account', 'what', 'subTitleIcon', 'journals', 'subTitle'));
    }

    /**
     * @param AccountFormRequest $request
     * @param ARI                $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AccountFormRequest $request, ARI $repository)
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

        $account = $repository->store($accountData);

        Session::flash('success', 'New account "' . $account->name . '" stored!');
        Preferences::mark();

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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AccountFormRequest $request, ARI $repository, Account $account)
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
        $repository->update($account, $accountData);

        Session::flash('success', 'Account "' . $account->name . '" updated.');
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
