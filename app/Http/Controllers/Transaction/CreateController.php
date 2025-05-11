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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Services\Internal\Update\GroupCloneService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private TransactionGroupRepositoryInterface $repository;

    /**
     * CreateController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');
                $this->repository = app(TransactionGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    public function cloneGroup(Request $request): JsonResponse
    {
        $groupId = (int) $request->get('id');
        if (0 !== $groupId) {
            $group = $this->repository->find($groupId);
            if (null !== $group) {
                /** @var GroupCloneService $service */
                $service  = app(GroupCloneService::class);
                $newGroup = $service->cloneGroup($group);

                // event!
                event(new StoredTransactionGroup($newGroup, true, true));

                app('preferences')->mark();

                $title    = $newGroup->title ?? $newGroup->transactionJournals->first()->description;
                $link     = route('transactions.show', [$newGroup->id]);
                session()->flash('success', trans('firefly.stored_journal', ['description' => $title]));
                session()->flash('success_url', $link);

                if ('edit' === $request->get('redirect')) {
                    return response()->json(['redirect' => route('transactions.edit', [$newGroup->id])]);
                }

                return response()->json(['redirect' => route('transactions.show', [$newGroup->id])]);
            }
        }

        return response()->json(['redirect' => route('transactions.show', [$groupId])]);
    }

    /**
     * Create a new transaction group.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     *                                              */
    public function create(?string $objectType)
    {
        app('preferences')->mark();

        $sourceId                   = (int) request()->get('source');
        $destinationId              = (int) request()->get('destination');

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository          = app(AccountRepositoryInterface::class);
        $cash                       = $accountRepository->getCashAccount();
        $preFilled                  = session()->has('preFilled') ? session('preFilled') : [];
        $subTitle                   = (string) trans(sprintf('breadcrumbs.create_%s', strtolower((string) $objectType)));
        $subTitleIcon               = 'fa-plus';
        $optionalFields             = app('preferences')->get('transaction_journal_optional_fields', [])->data;
        $allowedOpposingTypes       = config('firefly.allowed_opposing_types');
        $accountToTypes             = config('firefly.account_to_transaction');
        $defaultCurrency            = $this->defaultCurrency;
        $previousUrl                = $this->rememberPreviousUrl('transactions.create.url');
        $parts                      = \Safe\parse_url($previousUrl);
        $search                     = sprintf('?%s', $parts['query'] ?? '');
        $previousUrl                = str_replace($search, '', $previousUrl);
        if (!is_array($optionalFields)) {
            $optionalFields = [];
        }
        // not really a fan of this, but meh.
        $optionalDateFields         = [
            'interest_date' => $optionalFields['interest_date'] ?? false,
            'book_date'     => $optionalFields['book_date'] ?? false,
            'process_date'  => $optionalFields['process_date'] ?? false,
            'due_date'      => $optionalFields['due_date'] ?? false,
            'payment_date'  => $optionalFields['payment_date'] ?? false,
            'invoice_date'  => $optionalFields['invoice_date'] ?? false,
        ];
        $optionalFields['external_url'] ??= false;
        $optionalFields['location']     ??= false;
        $optionalFields['location'] = $optionalFields['location'] && true === config('firefly.enable_external_map');

        // map info:
        $longitude                  = config('firefly.default_location.longitude');
        $latitude                   = config('firefly.default_location.latitude');
        $zoomLevel                  = config('firefly.default_location.zoom_level');

        session()->put('preFilled', $preFilled);

        return view(
            'transactions.create',
            compact(
                'subTitleIcon',
                'cash',
                'longitude',
                'latitude',
                'zoomLevel',
                'objectType',
                'optionalDateFields',
                'subTitle',
                'defaultCurrency',
                'previousUrl',
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
