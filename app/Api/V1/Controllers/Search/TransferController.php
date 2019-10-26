<?php
/**
 * TransferController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers\Search;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Search\TransferRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Support\Search\TransferSearch;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class TransferController
 */
class TransferController extends Controller
{
    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     * @throws FireflyException
     */
    public function search(TransferRequest $request)
    {
        // configure transfer search to search for a > b
        $search = app(TransferSearch::class);
        $search->setSource($request->get('source'));
        $search->setDestination($request->get('destination'));
        $search->setAmount($request->get('amount'));
        $search->setDescription($request->get('description'));
        $search->setDate($request->get('date'));

        $left = $search->search();

        // configure transfer search to search for b > a
        $search->setSource($request->get('destination'));
        $search->setDestination($request->get('source'));
        $search->setAmount($request->get('amount'));
        $search->setDescription($request->get('description'));
        $search->setDate($request->get('date'));

        $right = $search->search();

        // add parameters to URL:
        $this->parameters->set('source', $request->get('source'));
        $this->parameters->set('destination', $request->get('destination'));
        $this->parameters->set('amount', $request->get('amount'));
        $this->parameters->set('description', $request->get('description'));
        $this->parameters->set('date', $request->get('date'));

        // get all journal ID's.
        $total = $left->merge($right)->unique('id')->pluck('id')->toArray();
        if (0 === count($total)) {
            // forces search to be empty.
            $total = [-1];
        }

        // collector to return results.
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $manager  = $this->getManager();
        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage(1)
            ->setJournalIds($total);

        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.search.transfers') . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }
}