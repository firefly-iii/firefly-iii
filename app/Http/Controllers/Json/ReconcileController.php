<?php
/**
 * ReconcileController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 *
 * Class ReconcileController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReconcileController extends Controller
{
    use UserNavigation;
    /** @var AccountRepositoryInterface The account repository */
    private $accountRepos;
    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepos;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * ReconcileController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));
                $this->repository    = app(JournalRepositoryInterface::class);
                $this->accountRepos  = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Overview of reconciliation.
     *
     * @param Request $request
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function overview(Request $request, Account $account, Carbon $start, Carbon $end): JsonResponse
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
        $transactions = $this->repository->getTransactionsById($transactionIds);
        $cleared      = $this->repository->getTransactionsById($clearedIds);
        $countCleared = 0;

        Log::debug('Start transaction loop');
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // find the account and opposing account for this transaction
            Log::debug(sprintf('Now at transaction #%d: %s', $transaction->journal_id, $transaction->description));
            $srcAccount  = $this->accountRepos->findNull((int)$transaction->account_id);
            $dstAccount  = $this->accountRepos->findNull((int)$transaction->opposing_account_id);
            $srcCurrency = (int)$this->accountRepos->getMetaValue($srcAccount, 'currency_id');
            $dstCurrency = (int)$this->accountRepos->getMetaValue($dstAccount, 'currency_id');

            // is $account source or destination?
            if ($account->id === $srcAccount->id) {
                // source, and it matches the currency id or is 0
                if ($srcCurrency === $transaction->transaction_currency_id || 0 === $srcCurrency) {
                    Log::debug(sprintf('Source matches currency: %s', $transaction->transaction_amount));
                    $amount = bcadd($amount, $transaction->transaction_amount);
                }
                // destination, and it matches the foreign currency ID.
                if ($srcCurrency === $transaction->foreign_currency_id) {
                    Log::debug(sprintf('Source matches foreign currency: %s', $transaction->transaction_foreign_amount));
                    $amount = bcadd($amount, $transaction->transaction_foreign_amount);
                }
            }

            if ($account->id === $dstAccount->id) {
                // destination, and it matches the currency id or is 0
                if ($dstCurrency === $transaction->transaction_currency_id || 0 === $dstCurrency) {
                    Log::debug(sprintf('Destination matches currency: %s', app('steam')->negative($transaction->transaction_amount)));
                    $amount = bcadd($amount, app('steam')->negative($transaction->transaction_amount));
                }
                // destination, and it matches the foreign currency ID.
                if ($dstCurrency === $transaction->foreign_currency_id) {
                    Log::debug(sprintf('Destination matches foreign currency: %s', $transaction->transaction_foreign_amount));
                    $amount = bcadd($amount, $transaction->transaction_foreign_amount);
                }
            }
            Log::debug(sprintf('Amount is now %s', $amount));
        }
        Log::debug('End transaction loop');
        /** @var Transaction $transaction */
        foreach ($cleared as $transaction) {
            if ($transaction->date <= $end) {
                $clearedAmount = bcadd($clearedAmount, $transaction->transaction_amount); // @codeCoverageIgnore
                ++$countCleared;
            }
        }
        $difference  = bcadd(bcadd(bcsub($startBalance, $endBalance), $clearedAmount), $amount);
        $diffCompare = bccomp($difference, '0');

        try {
            $view = view(
                'accounts.reconcile.overview', compact(
                                                 'account', 'start', 'diffCompare', 'difference', 'end', 'clearedIds', 'transactionIds', 'clearedAmount',
                                                 'startBalance', 'endBalance', 'amount',
                                                 'route', 'countCleared'
                                             )
            )->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('View error: %s', $e->getMessage()));
            $view = 'Could not render accounts.reconcile.overview';
        }
        // @codeCoverageIgnoreEnd


        $return = [
            'post_uri' => $route,
            'html'     => $view,
        ];

        return response()->json($return);
    }


    /**
     * Returns a list of transactions in a modal.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     *
     */
    public function transactions(Account $account, Carbon $start, Carbon $end)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }

        $startDate = clone $start;
        $startDate->subDays(1);

        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $currency   = $this->currencyRepos->findNull($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency(); // @codeCoverageIgnore
        }

        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);

        // get the transactions
        $selectionStart = clone $start;
        $selectionStart->subDays(3);
        $selectionEnd = clone $end;
        $selectionEnd->addDays(3);

        // grab transactions:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))
                  ->setRange($selectionStart, $selectionEnd)->withBudgetInformation()->withOpposingAccount()->withCategoryInformation();
        $transactions = $collector->getTransactions();
        try {
            $html = view(
                'accounts.reconcile.transactions', compact('account', 'transactions', 'currency', 'start', 'end', 'selectionStart', 'selectionEnd')
            )->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render: %s', $e->getMessage()));
            $html = 'Could not render accounts.reconcile.transactions';
        }

        // @codeCoverageIgnoreEnd

        return response()->json(['html' => $html, 'startBalance' => $startBalance, 'endBalance' => $endBalance]);
    }
}
