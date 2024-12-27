<?php

/**
 * TransactionController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Autocomplete;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    private TransactionGroupRepositoryInterface $groupRepository;
    private JournalRepositoryInterface          $repository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                  = auth()->user();
                $this->repository      = app(JournalRepositoryInterface::class);
                $this->groupRepository = app(TransactionGroupRepositoryInterface::class);
                $this->repository->setUser($user);
                $this->groupRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getTransactionsAC
     */
    public function transactions(AutocompleteRequest $request): JsonResponse
    {
        $data     = $request->getData();
        $result   = $this->repository->searchJournalDescriptions($data['query'], $this->parameters->get('limit'));

        // limit and unique
        $filtered = $result->unique('description');
        $array    = [];

        /** @var TransactionJournal $journal */
        foreach ($filtered as $journal) {
            $array[] = [
                'id'                   => (string) $journal->id,
                'transaction_group_id' => (string) $journal->transaction_group_id,
                'name'                 => $journal->description,
                'description'          => $journal->description,
            ];
        }

        return response()->json($array);
    }

    /**
     * This endpoint is documented at:
     * * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getTransactionsIDAC
     */
    public function transactionsWithID(AutocompleteRequest $request): JsonResponse
    {
        $data   = $request->getData();
        $result = new Collection();
        if (is_numeric($data['query'])) {
            // search for group, not journal.
            $firstResult = $this->groupRepository->find((int) $data['query']);
            if (null !== $firstResult) {
                // group may contain multiple journals, each a result:
                foreach ($firstResult->transactionJournals as $journal) {
                    $result->push($journal);
                }
            }
        }
        if (!is_numeric($data['query'])) {
            $result = $this->repository->searchJournalDescriptions($data['query'], $this->parameters->get('limit'));
        }

        // limit and unique
        $array  = [];

        /** @var TransactionJournal $journal */
        foreach ($result as $journal) {
            $array[] = [
                'id'                   => (string) $journal->id,
                'transaction_group_id' => (string) $journal->transaction_group_id,
                'name'                 => sprintf('#%d: %s', $journal->transaction_group_id, $journal->description),
                'description'          => sprintf('#%d: %s', $journal->transaction_group_id, $journal->description),
            ];
        }

        return response()->json($array);
    }
}
