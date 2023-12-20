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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private JournalRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                $this->repository = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function edit(TransactionGroup $transactionGroup)
    {
        app('preferences')->mark();

        if (!$this->isEditableGroup($transactionGroup)) {
            return $this->redirectGroupToAccount($transactionGroup);
        }

        /** @var AccountRepositoryInterface $repository */
        $repository           = app(AccountRepositoryInterface::class);
        $allowedOpposingTypes = config('firefly.allowed_opposing_types');
        $accountToTypes       = config('firefly.account_to_transaction');
        $expectedSourceTypes  = config('firefly.expected_source_types');
        $allowedSourceDests   = config('firefly.source_dests');

        $defaultCurrency = app('amount')->getDefaultCurrency();
        $cash            = $repository->getCashAccount();
        $previousUrl     = $this->rememberPreviousUrl('transactions.edit.url');
        $parts           = parse_url($previousUrl);
        $search          = sprintf('?%s', $parts['query'] ?? '');
        $previousUrl     = str_replace($search, '', $previousUrl);

        return view(
            'transactions.edit',
            compact(
                'cash',
                'allowedSourceDests',
                'expectedSourceTypes',
                'transactionGroup',
                'allowedOpposingTypes',
                'accountToTypes',
                'defaultCurrency',
                'previousUrl'
            )
        );
    }

    public function unreconcile(TransactionJournal $journal): JsonResponse
    {
        $this->repository->unreconcileById($journal->id);

        return response()->json([], 204);
    }
}
