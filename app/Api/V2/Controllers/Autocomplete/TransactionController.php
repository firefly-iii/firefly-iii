<?php

/*
 * TransactionController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Autocomplete;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Autocomplete\AutocompleteRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\UserGroups\Journal\JournalRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    private JournalRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(JournalRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));

                return $next($request);
            }
        );
    }

    /**
     * Documentation: https://api-docs.firefly-iii.org/?urls.primaryName=2.1.0%20(v2)#/autocomplete/getTransactionsAC
     */
    public function transactionDescriptions(AutocompleteRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $result          = $this->repository->searchJournalDescriptions($queryParameters['query'], $queryParameters['size']);

        // limit and unique
        $filtered        = $result->unique('description');
        $array           = [];

        /** @var TransactionJournal $journal */
        foreach ($filtered as $journal) {
            $array[] = [
                'id'    => (string) $journal->id,
                'title' => $journal->description,
                'meta'  => [
                    'transaction_group_id' => (string) $journal->transaction_group_id,
                ],
            ];
        }

        return response()->json($array);
    }
}
