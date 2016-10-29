<?php
/**
 * ConvertController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Transaction;

use ExpandedForm;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Http\Request;
use Session;
use View;

/**
 * Class ConvertController
 *
 * @package FireflyIII\Http\Controllers\Transaction
 */
class ConvertController extends Controller
{
    /** @var  AccountRepositoryInterface */
    private $accounts;

    /**
     * ConvertController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->accounts = app(AccountRepositoryInterface::class);

                View::share('title', trans('firefly.transactions'));
                View::share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionType    $destinationType
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function convert(TransactionType $destinationType, TransactionJournal $journal)
    {
        $positiveAmount = TransactionJournal::amountPositive($journal);
        $assetAccounts  = ExpandedForm::makeSelectList($this->accounts->getActiveAccountsByType([AccountType::DEFAULT, AccountType::ASSET]));
        $sourceType     = $journal->transactionType;

        $subTitle     = trans('firefly.convert_to_' . $destinationType->type, ['description' => $journal->description]);
        $subTitleIcon = 'fa-exchange';

        if ($sourceType->type === $destinationType->type) {
            Session::flash('info', trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$journal->id]));
        }
        if ($journal->transactions()->count() > 2) {
            Session::flash('error', trans('firefly.cannot_convert_split_journl'));

            return redirect(route('transactions.show', [$journal->id]));
        }
        $sourceAccount      = TransactionJournal::sourceAccountList($journal)->first();
        $destinationAccount = TransactionJournal::destinationAccountList($journal)->first();

        return view(
            'transactions.convert', compact(
                                      'sourceType', 'destinationType', 'journal', 'assetAccounts',
                                      'positiveAmount', 'sourceAccount', 'destinationAccount', 'sourceType',
                                      'subTitle', 'subTitleIcon'

                                  )
        );


        // convert withdrawal to deposit requires a new source account ()
        //  or to transfer requires
    }

    public function submit(Request $request)
    {
        echo '<pre>';

        var_dump($request->all());


        exit;
    }

}