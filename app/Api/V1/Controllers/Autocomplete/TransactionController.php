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
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteApiRequest;
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteTransactionApiRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    protected array $acceptedRoles = [UserRoleEnum::READ_ONLY];
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
                $this->validateUserGroup($request);
                $this->repository      = app(JournalRepositoryInterface::class);
                $this->groupRepository = app(TransactionGroupRepositoryInterface::class);
                $this->repository->setUser($this->user);
                $this->repository->setUserGroup($this->userGroup);
                $this->groupRepository->setUser($this->user);
                $this->groupRepository->setUserGroup($this->userGroup);

                return $next($request);
            }
        );
    }

    public function transactions(AutocompleteTransactionApiRequest $request): JsonResponse
    {
        $result   = $this->repository->searchJournalDescriptions($request->attributes->get('query'), $request->attributes->get('limit'));

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

        return response()->api($array);
    }

    public function transactionsWithID(AutocompleteApiRequest $request): JsonResponse
    {
        $result = new Collection();
        if (is_numeric($request->attributes->get('query'))) {
            // search for group, not journal.
            $firstResult = $this->groupRepository->find((int) $request->attributes->get('query'));
            if ($firstResult instanceof TransactionGroup) {
                // group may contain multiple journals, each a result:
                foreach ($firstResult->transactionJournals as $journal) {
                    $result->push($journal);
                }
            }
        }
        if (!is_numeric($request->attributes->get('query'))) {
            $result = $this->repository->searchJournalDescriptions($request->attributes->get('query'), $request->attributes->get('limit'));
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

        return response()->api($array);
    }
}
