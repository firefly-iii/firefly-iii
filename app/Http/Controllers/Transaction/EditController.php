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

use function Safe\parse_url;
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
                app('view')->share('title', (string) trans('firefly.transactions'));
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
        $repository                 = app(AccountRepositoryInterface::class);
        $allowedOpposingTypes       = config('firefly.allowed_opposing_types');
        $accountToTypes             = config('firefly.account_to_transaction');
        $expectedSourceTypes        = config('firefly.expected_source_types');
        $allowedSourceDests         = config('firefly.source_dests');
        $title                      = $transactionGroup->transactionJournals()->count() > 1 ? $transactionGroup->title : $transactionGroup->transactionJournals()->first()->description;
        $subTitle                   = (string) trans('firefly.edit_transaction_title', ['description' => $title]);
        $subTitleIcon               = 'fa-plus';
        $defaultCurrency            = $this->defaultCurrency;
        $cash                       = $repository->getCashAccount();
        $previousUrl                = $this->rememberPreviousUrl('transactions.edit.url');
        $parts                      = parse_url((string) $previousUrl);
        $search                     = sprintf('?%s', $parts['query'] ?? '');
        $previousUrl                = str_replace($search, '', $previousUrl);

        // settings necessary for v2
        $optionalFields             = app('preferences')->get('transaction_journal_optional_fields', [])->data;
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

        // map info voor v2:
        $longitude                  = config('firefly.default_location.longitude');
        $latitude                   = config('firefly.default_location.latitude');
        $zoomLevel                  = config('firefly.default_location.zoom_level');

        return view(
            'transactions.edit',
            compact(
                'cash',
                'allowedSourceDests',
                'expectedSourceTypes',
                'optionalDateFields',
                'longitude',
                'latitude',
                'zoomLevel',
                'optionalFields',
                'subTitle',
                'subTitleIcon',
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
