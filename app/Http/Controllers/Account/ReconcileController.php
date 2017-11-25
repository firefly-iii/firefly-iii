<?php
/**
 * ReconcileController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ReconciliationFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use Session;
use View;

/**
 * Class ReconcileController.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReconcileController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-credit-card');
                View::share('title', trans('firefly.accounts'));

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit(TransactionJournal $journal)
    {
        if ($journal->transactionType->type !== TransactionType::RECONCILIATION) {
            return redirect(route('transactions.edit', [$journal->id]));
        }
        // view related code
        $subTitle = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);

        // journal related code
        $pTransaction = $journal->positiveTransaction();
        $preFilled    = [
            'date'     => $journal->dateAsString(),
            'category' => $journal->categoryAsString(),
            'tags'     => join(',', $journal->tags->pluck('tag')->toArray()),
            'amount'   => $pTransaction->amount,
        ];

        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-reconciliation');

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('reconcile.edit.fromUpdate')) {
            $this->rememberPreviousUri('reconcile.edit.uri');
        }
        Session::forget('reconcile.edit.fromUpdate');

        return view(
            'accounts.reconcile.edit',
            compact('journal', 'subTitle')
        )->with('data', $preFilled);

    }

    /**
     * @param Request $request
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws FireflyException
     */
    public function overview(Request $request, Account $account, Carbon $start, Carbon $end)
    {
        if (AccountType::ASSET !== $account->accountType->type) {
            throw new FireflyException(sprintf('Account %s is not an asset account.', $account->name));
        }
        $startBalance   = $request->get('startBalance');
        $endBalance     = $request->get('endBalance');
        $transactionIds = $request->get('transactions') ?? [];
        $clearedIds     = $request->get('cleared') ?? [];
        $amount         = '0';
        $clearedAmount  = '0';
        $route          = route('accounts.reconcile.submit', [$account->id, $start->format('Ymd'), $end->format('Ymd')]);
        // get sum of transaction amounts:
        /** @var JournalRepositoryInterface $repository */
        $repository   = app(JournalRepositoryInterface::class);
        $transactions = $repository->getTransactionsById($transactionIds);
        $cleared      = $repository->getTransactionsById($clearedIds);
        $countCleared = 0;

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $amount = bcadd($amount, $transaction->amount);
        }

        /** @var Transaction $transaction */
        foreach ($cleared as $transaction) {
            if ($transaction->transactionJournal->date <= $end) {
                $clearedAmount = bcadd($clearedAmount, $transaction->amount);
                ++$countCleared;
            }
        }
        $difference  = bcadd(bcadd(bcsub($startBalance, $endBalance), $clearedAmount), $amount);
        $diffCompare = bccomp($difference, '0');
        $return         = [
            'post_uri' => $route,
            'html'     => view('accounts.reconcile.overview', compact('account', 'start', 'diffCompare', 'difference', 'end', 'clearedIds', 'transactionIds', 'clearedAmount', 'startBalance', 'endBalance', 'amount', 'route', 'countCleared'))->render(),
        ];

        return Response::json($return);
    }

    /**
     * @param Account     $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function reconcile(Account $account, Carbon $start = null, Carbon $end = null)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }
        if (AccountType::ASSET !== $account->accountType->type) {
            Session::flash('error', trans('firefly.must_be_asset_account'));

            return redirect(route('accounts.index', [config('firefly.shortNamesByFullName.' . $account->accountType->type)]));
        }
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);
        $currencyId    = intval($account->getMeta('currency_id'));
        $currency      = $currencyRepos->find($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency();
        }

        // no start or end:
        $range = Preferences::get('viewRange', '1M')->data;

        // get start and end
        if (null === $start && null === $end) {
            $start = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            $end   = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
        }
        if (null === $end) {
            $end = Navigation::endOfPeriod($start, $range);
        }

        $startDate = clone $start;
        $startDate->subDays(1);
        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $subTitle     = trans('firefly.reconcile_account', ['account' => $account->name]);

        // various links
        $transactionsUri = route('accounts.reconcile.transactions', [$account->id, '%start%', '%end%']);
        $overviewUri     = route('accounts.reconcile.overview', [$account->id, '%start%', '%end%']);
        $indexUri        = route('accounts.reconcile', [$account->id, '%start%', '%end%']);

        return view('accounts.reconcile.index', compact('account', 'currency', 'subTitleIcon', 'start', 'end', 'subTitle', 'startBalance', 'endBalance', 'transactionsUri', 'overviewUri', 'indexUri'));
    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function show(JournalRepositoryInterface $repository, TransactionJournal $journal)
    {
        if ($journal->transactionType->type !== TransactionType::RECONCILIATION) {
            return redirect(route('transactions.show', [$journal->id]));
        }
        $subTitle = trans('firefly.reconciliation') . ' "' . $journal->description . '"';

        // get main transaction:
        $transaction = $repository->getAssetTransaction($journal);


        return view('accounts.reconcile.show', compact('journal', 'subTitle', 'transaction'));
    }

    /**
     * @param Request $request
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submit(Request $request, Account $account, Carbon $start, Carbon $end)
    {
        /** @var JournalRepositoryInterface $repository */
        $repository   = app(JournalRepositoryInterface::class);
        $transactions = $repository->getTransactionsById($request->get('transactions'));
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $repository->reconcile($transaction); // mark as reconciled.
        }

        // create reconciliation transaction (if necessary):
        if ('create' === $request->get('reconcile')) {
            /** @var AccountRepositoryInterface $accountRepos */
            $accountRepos   = app(AccountRepositoryInterface::class);
            $reconciliation = $accountRepos->getReconciliation($account);
            $difference     = $request->get('difference');

            // store journal between these two.
            $data    = [
                'what'        => 'Reconciliation',
                'source'      => $account,
                'destination' => $reconciliation,
                'category'    => '',
                'budget_id'   => 0,
                'amount'      => $difference,
                'currency_id' => $account->getMeta('currency_id'),
                'description' => trans(
                    'firefly.reconcilliation_transaction_title',
                    ['from' => $start->formatLocalized($this->monthAndDayFormat), 'to' => $end->formatLocalized($this->monthAndDayFormat)]
                ),
                'date'        => $request->get('end'),
                'notes'       => join(',', $transactions->pluck('id')->toArray()),
            ];
            $journal = $repository->store($data);
            // reconcile this transaction too:
            $transaction = $journal->transactions()->first();
            $repository->reconcile($transaction);
        }

        Session::flash('success', trans('firefly.reconciliation_stored'));

        return redirect(route('accounts.show', [$account->id]));
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     */
    public function transactions(Account $account, Carbon $start, Carbon $end)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }

        $startDate = clone $start;
        $startDate->subDays(1);

        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);
        $currencyId    = intval($account->getMeta('currency_id'));
        $currency      = $currencyRepos->find($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency();
        }

        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);

        // get the transactions
        $selectionStart = clone $start;
        $selectionStart->subDays(3);
        $selectionEnd = clone $end;
        $selectionEnd->addDays(3);

        // grab transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))
                  ->setRange($selectionStart, $selectionEnd)->withBudgetInformation()->withOpposingAccount()->withCategoryInformation();
        $transactions = $collector->getJournals();
        $html         = view('accounts.reconcile.transactions', compact('account', 'transactions', 'start', 'end', 'selectionStart', 'selectionEnd'))->render();

        return Response::json(['html' => $html, 'startBalance' => $startBalance, 'endBalance' => $endBalance]);
    }

    /**
     * @param ReconciliationFormRequest  $request
     * @param AccountRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(ReconciliationFormRequest $request, AccountRepositoryInterface $repository, TransactionJournal $journal)
    {
        if ($journal->transactionType->type !== TransactionType::RECONCILIATION) {
            return redirect(route('transactions.show', [$journal->id]));
        }
        if (bccomp('0', $request->get('amount')) === 0) {
            Session::flash('error', trans('firefly.amount_cannot_be_zero'));

            return redirect(route('accounts.reconcile.edit', [$journal->id]))->withInput();
        }
        // update journal using account repository. Keep it consistent.
        $data = $request->getJournalData();
        $repository->updateReconciliation($journal, $data);

        // @codeCoverageIgnoreStart
        if (1 === intval($request->get('return_to_edit'))) {
            Session::put('reconcile.edit.fromUpdate', true);

            return redirect(route('accounts.reconcile.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('reconcile.edit.uri'));

    }


    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    private function redirectToOriginalAccount(Account $account)
    {
        /** @var Transaction $transaction */
        $transaction = $account->transactions()->first();
        if (null === $transaction) {
            throw new FireflyException('Expected a transaction. This account has none. BEEP, error.');
        }

        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposingTransaction */
        $opposingTransaction = $journal->transactions()->where('transactions.id', '!=', $transaction->id)->first();

        if (null === $opposingTransaction) {
            throw new FireflyException('Expected an opposing transaction. This account has none. BEEP, error.'); // @codeCoverageIgnore
        }

        return redirect(route('accounts.show', [$opposingTransaction->account_id]));
    }
}
