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
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use View;

/**
 * Class ReconcileController
 *
 * @package FireflyIII\Http\Controllers\Account
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
     * @param Request $request
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function overview(Request $request, Account $account, Carbon $start, Carbon $end)
    {
        $startBalance = $request->get('startBalance');
        $endBalance   = $request->get('endBalance');
        $transactions = $request->get('transactions');

        $return         = [
            'is_zero'  => false,
            'post_uri' => route('accounts.reconcile.submit', [$account->id, $start->format('Ymd'), $end->format('Ymd')]),
            'html'     => '',
        ];
        $return['html'] = view('accounts.reconcile.overview', compact('account', 'start', 'end'))->render();

        return Response::json($return);
    }

    /**
     * @param Account     $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reconcile(Account $account, Carbon $start = null, Carbon $end = null)
    {
        if ($account->accountType->type === AccountType::INITIAL_BALANCE) {
            return $this->redirectToOriginalAccount($account);
        }
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);
        $currencyId    = intval($account->getMeta('currency_id'));
        $currency      = $currencyRepos->find($currencyId);
        if ($currencyId === 0) {
            $currency = app('amount')->getDefaultCurrency();
        }

        // no start or end:
        $range = Preferences::get('viewRange', '1M')->data;

        // get start and end
        if (is_null($start) && is_null($end)) {
            $start = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            $end   = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
        }
        if (is_null($end)) {
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

        return view(
            'accounts.reconcile.index',
            compact(
                'account',
                'currency',
                'subTitleIcon',
                'start',
                'end',
                'subTitle',
                'startBalance',
                'endBalance',
                'transactionsUri',
                'selectionStart',
                'selectionEnd',
                'overviewUri',
                'indexUri'
            )
        );
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
        if ($account->accountType->type === AccountType::INITIAL_BALANCE) {
            return $this->redirectToOriginalAccount($account);
        }

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

        return Response::json(['html' => $html]);
    }
}
