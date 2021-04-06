<?php
/**
 * EditController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    /**
     * EditController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            static function ($request, $next) {

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionGroup $transactionGroup
     *
     * @return Factory|View|RedirectResponse|Redirector
     */
    public function edit(TransactionGroup $transactionGroup)
    {
        app('preferences')->mark();

        if (!$this->isEditableGroup($transactionGroup)) {
            return $this->redirectGroupToAccount($transactionGroup); // @codeCoverageIgnore
        }

        /** @var AccountRepositoryInterface $repository */
        $repository           = app(AccountRepositoryInterface::class);
        $allowedOpposingTypes = config('firefly.allowed_opposing_types');
        $accountToTypes       = config('firefly.account_to_transaction');
        $expectedSourceTypes  = config('firefly.expected_source_types');
        $allowedSourceDests   = config('firefly.source_dests');
        //

        $defaultCurrency = app('amount')->getDefaultCurrency();
        $cash            = $repository->getCashAccount();
        $previousUri     = $this->rememberPreviousUri('transactions.edit.uri');
        $parts           = parse_url($previousUri);
        $search          = sprintf('?%s', $parts['query'] ?? '');
        $previousUri     = str_replace($search, '', $previousUri);

        return prefixView(
            'transactions.edit',
            compact(
                'cash', 'allowedSourceDests', 'expectedSourceTypes', 'transactionGroup', 'allowedOpposingTypes', 'accountToTypes', 'defaultCurrency',
                'previousUri'
            )
        );
    }

}
