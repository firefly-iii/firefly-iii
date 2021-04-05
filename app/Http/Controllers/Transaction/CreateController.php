<?php
/**
 * CreateController.php
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

use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Update\GroupCloneService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    /**
     * CreateController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            static function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionGroup $group
     *
     * @return RedirectResponse|Redirector
     */
    public function cloneGroup(TransactionGroup $group)
    {

        /** @var GroupCloneService $service */
        $service  = app(GroupCloneService::class);
        $newGroup = $service->cloneGroup($group);

        // event!
        event(new StoredTransactionGroup($newGroup, true));

        app('preferences')->mark();

        $title = $newGroup->title ?? $newGroup->transactionJournals->first()->description;
        $link  = route('transactions.show', [$newGroup->id]);
        session()->flash('success', trans('firefly.stored_journal', ['description' => $title]));
        session()->flash('success_url', $link);

        return redirect(route('transactions.show', [$newGroup->id]));
    }

    /**
     * Create a new transaction group.
     *
     * @param string|null objectType
     *
     * @return Factory|View
     */
    public function create(?string $objectType)
    {
        app('preferences')->mark();

        $sourceId      = (int)request()->get('source');
        $destinationId = (int)request()->get('destination');

        /** @var AccountRepositoryInterface $repository */
        $repository           = app(AccountRepositoryInterface::class);
        $cash                 = $repository->getCashAccount();
        $preFilled            = session()->has('preFilled') ? session('preFilled') : [];
        $subTitle             = (string)trans('breadcrumbs.create_new_transaction');
        $subTitleIcon         = 'fa-plus';
        $optionalFields       = app('preferences')->get('transaction_journal_optional_fields', [])->data;
        $allowedOpposingTypes = config('firefly.allowed_opposing_types');
        $accountToTypes       = config('firefly.account_to_transaction');
        $defaultCurrency      = app('amount')->getDefaultCurrency();
        $previousUri          = $this->rememberPreviousUri('transactions.create.uri');
        $parts                = parse_url($previousUri);
        $search               = sprintf('?%s', $parts['query'] ?? '');
        $previousUri          = str_replace($search, '', $previousUri);

        session()->put('preFilled', $preFilled);

        return prefixView(
            'transactions.create',
            compact(
                'subTitleIcon',
                'cash',
                'objectType',
                'subTitle',
                'defaultCurrency',
                'previousUri',
                'optionalFields',
                'preFilled',
                'allowedOpposingTypes',
                'accountToTypes',
                'sourceId',
                'destinationId'
            )
        );
    }
}
