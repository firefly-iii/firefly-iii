<?php
/**
 * NewUserController.php
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
use FireflyIII\Http\Requests\NewUserFormRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Preferences;
use Session;
use View;

/**
 * Class NewUserController
 *
 * @package FireflyIII\Http\Controllers
 */
class NewUserController extends Controller
{
    /**
     * NewUserController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function index(AccountRepositoryInterface $repository)
    {

        View::share('title', trans('firefly.welcome'));
        View::share('mainTitleIcon', 'fa-fire');


        $types = config('firefly.accountTypesByIdentifier.asset');
        $count = $repository->count($types);

        if ($count > 0) {
            return redirect(route('index'));

        }

        return view('new-user.index');
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submit(NewUserFormRequest $request, AccountRepositoryInterface $repository)
    {
        $count = 1;
        // create normal asset account:
        $this->createAssetAccount($request, $repository);

        // create savings account
        if (strlen($request->get('savings_balance')) > 0) {
            $this->createSavingsAccount($request, $repository);
            $count++;
        }


        // create credit card.
        if (strlen($request->get('credit_card_limit')) > 0) {
            $this->storeCreditCard($request, $repository);
            $count++;
        }
        $message = strval(trans('firefly.stored_new_accounts_new_user'));
        if ($count == 1) {
            $message = strval(trans('firefly.stored_new_account_new_user'));
        }

        Session::flash('success', $message);
        Preferences::mark();

        return redirect(route('index'));
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return bool
     */
    private function createAssetAccount(NewUserFormRequest $request, AccountRepositoryInterface $repository): bool
    {
        $assetAccount = [
            'name'                   => $request->get('bank_name'),
            'iban'                   => null,
            'accountType'            => 'asset',
            'virtualBalance'         => 0,
            'active'                 => true,
            'accountRole'            => 'defaultAsset',
            'openingBalance'         => round($request->input('bank_balance'), 2),
            'openingBalanceDate'     => new Carbon,
            'openingBalanceCurrency' => intval($request->input('amount_currency_id_bank_balance')),
        ];

        $repository->store($assetAccount);

        return true;
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return bool
     */
    private function createSavingsAccount(NewUserFormRequest $request, AccountRepositoryInterface $repository): bool
    {
        $savingsAccount = [
            'name'                   => $request->get('bank_name') . ' savings account',
            'iban'                   => null,
            'accountType'            => 'asset',
            'virtualBalance'         => 0,
            'active'                 => true,
            'accountRole'            => 'savingAsset',
            'openingBalance'         => round($request->input('savings_balance'), 2),
            'openingBalanceDate'     => new Carbon,
            'openingBalanceCurrency' => intval($request->input('amount_currency_id_savings_balance')),
        ];
        $repository->store($savingsAccount);

        return true;
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return bool
     */
    private function storeCreditCard(NewUserFormRequest $request, AccountRepositoryInterface $repository): bool
    {
        $creditAccount = [
            'name'                   => 'Credit card',
            'iban'                   => null,
            'accountType'            => 'asset',
            'virtualBalance'         => round($request->get('credit_card_limit'), 2),
            'active'                 => true,
            'accountRole'            => 'ccAsset',
            'openingBalance'         => null,
            'openingBalanceDate'     => null,
            'openingBalanceCurrency' => intval($request->input('amount_currency_id_credit_card_limit')),
            'ccType'                 => 'monthlyFull',
            'ccMonthlyPaymentDate'   => Carbon::now()->year . '-01-01',
        ];
        $repository->store($creditAccount);

        return true;
    }
}
