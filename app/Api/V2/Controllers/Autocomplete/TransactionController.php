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

                $userGroup = $this->validateUserGroup($request);
                if (null !== $userGroup) {
                    $this->repository->setUserGroup($userGroup);
                }

                return $next($request);
            }
        );
    }

    /**
     *  Documentation for this endpoint:
     *  TODO list of checks
     *  1. use dates from ParameterBag
     *  2. Request validates dates
     *  3. Request includes user_group_id
     *  4. Endpoint is documented.
     *  5. Collector uses user_group_id
     */
    public function transactionDescriptions(AutocompleteRequest $request): JsonResponse
    {
        $data   = $request->getData();
        $result = $this->repository->searchJournalDescriptions($data['query'], $data['limit']);

        // limit and unique
        $filtered = $result->unique('description');
        $array    = [];

        /** @var TransactionJournal $journal */
        foreach ($filtered as $journal) {
            $array[] = [
                'id'                   => (string)$journal->id,
                'transaction_group_id' => (string)$journal->transaction_group_id,
                'name'                 => $journal->description,
                'description'          => $journal->description,
            ];
        }

        return response()->json($array);
    }
}
