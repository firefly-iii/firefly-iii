<?php
/**
 * AutoCompleteController.php
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

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AutoCompleteController.
 *
 * TODO autocomplete for transaction types.
 *
 */
class AutoCompleteController extends Controller
{


    /**
     * Searches in the titles of all transaction journals.
     * The result is limited to the top 15 unique results.
     *
     * If the query is numeric, it will append the journal with that particular ID.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function allJournalsWithID(Request $request): JsonResponse
    {
        $search = (string)$request->get('search');
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);

        /** @var TransactionGroupRepositoryInterface $groupRepos */
        $groupRepos = app(TransactionGroupRepositoryInterface::class);

        $result = $repository->searchJournalDescriptions($search);
        $array  = [];
        if (is_numeric($search)) {
            // search for group, not journal.
            $firstResult = $groupRepos->find((int)$search);
            if (null !== $firstResult) {
                // group may contain multiple journals, each a result:
                foreach ($firstResult->transactionJournals as $journal) {
                    $array[] = $journal->toArray();
                }
            }
        }
        // if not numeric, search ahead!

        // limit and unique
        $limited = $result->slice(0, 15);
        $array   = array_merge($array, $limited->toArray());
        foreach ($array as $index => $item) {
            // give another key for consistency
            $array[$index]['name'] = sprintf('#%d: %s', $item['transaction_group_id'], $item['description']);
        }


        return response()->json($array);
    }

}
