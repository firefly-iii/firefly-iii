<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use FireflyIII\Http\Requests\NewUserFormRequest;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Redirect;
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
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\View\View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        View::share('title', 'Welcome to Firefly!');
        View::share('mainTitleIcon', 'fa-fire');


        $types = Config::get('firefly.accountTypesByIdentifier.asset');
        $count = $repository->countAccounts($types);

        if ($count > 0) {
            return Redirect::route('index');

        }

        return view('new-user.index');
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     */
    public function submit(NewUserFormRequest $request, AccountRepositoryInterface $repository)
    {

        // create normal asset account:
        $assetAccount = [
            'name'                   => $request->get('bank_name'),
            'accountType'            => 'asset',
            'virtualBalance'         => 0,
            'active'                 => true,
            'user'                   => Auth::user()->id,
            'accountRole'            => 'defaultAsset',
            'openingBalance'         => floatval($request->input('bank_balance')),
            'openingBalanceDate'     => new Carbon,
            'openingBalanceCurrency' => intval($request->input('balance_currency_id')),
        ];

        $repository->store($assetAccount);

        // create savings account
        if (strlen($request->get('savings_balance') > 0)) {
            $savingsAccount = [
                'name'                   => $request->get('bank_name') . ' savings account',
                'accountType'            => 'asset',
                'virtualBalance'         => 0,
                'active'                 => true,
                'user'                   => Auth::user()->id,
                'accountRole'            => 'savingAsset',
                'openingBalance'         => floatval($request->input('savings_balance')),
                'openingBalanceDate'     => new Carbon,
                'openingBalanceCurrency' => intval($request->input('balance_currency_id')),
            ];
            $repository->store($savingsAccount);
        }


        // create credit card.
        if (strlen($request->get('credit_card_limit') > 0)) {
            $creditAccount = [
                'name'                   => 'Credit card',
                'accountType'            => 'asset',
                'virtualBalance'         => floatval($request->get('credit_card_limit')),
                'active'                 => true,
                'user'                   => Auth::user()->id,
                'accountRole'            => 'ccAsset',
                'openingBalance'         => null,
                'openingBalanceDate'     => null,
                'openingBalanceCurrency' => intval($request->input('balance_currency_id')),
            ];
            $creditCard    = $repository->store($creditAccount);

            // store meta for CC:
            AccountMeta::create(
                [
                    'name'       => 'ccType',
                    'data'       => 'monthlyFull',
                    'account_id' => $creditCard->id,
                ]
            );
            AccountMeta::create(
                [
                    'name'       => 'ccMonthlyPaymentDate',
                    'data'       => Carbon::now()->year.'-01-01',
                    'account_id' => $creditCard->id,
                ]
            );

        }
        Session::flash('success', 'New account(s) created!');

        return Redirect::route('home');
    }
}