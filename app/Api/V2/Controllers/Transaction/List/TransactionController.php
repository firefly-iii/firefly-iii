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

namespace FireflyIII\Api\V2\Controllers\Transaction\List;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Transaction\InfiniteListRequest;
use FireflyIII\Api\V2\Request\Model\Transaction\ListRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Transformers\V2\TransactionGroupTransformer;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    public function infiniteList(InfiniteListRequest $request): JsonResponse
    {
        // get sort instructions
        $instructions = $request->getSortInstructions('transactions');

        // collect transactions:
        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setUserGroup(auth()->user()->userGroup)
            ->withAPIInformation()
            ->setStartRow($request->getStartRow())
            ->setEndRow($request->getEndRow())
            ->setTypes($request->getTransactionTypes())
            ->setSorting($instructions)
        ;

        $start        = $this->parameters->get('start');
        $end          = $this->parameters->get('end');
        if (null !== $start) {
            $collector->setStart($start);
        }
        if (null !== $end) {
            $collector->setEnd($end);
        }

        $paginator    = $collector->getPaginatedGroups();
        $params       = $request->buildParams();
        $paginator->setPath(
            sprintf(
                '%s?%s',
                route('api.v2.infinite.transactions.list'),
                $params
            )
        );

        return response()
            ->json($this->jsonApiList('transactions', $paginator, new TransactionGroupTransformer()))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }

    public function list(ListRequest $request): JsonResponse
    {
        // collect transactions:
        $pageSize  = $this->parameters->get('limit');
        $page      = $request->getPage();
        $page      = max($page, 1);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUserGroup(auth()->user()->userGroup)
            ->withAPIInformation()
            ->setLimit($pageSize)
            ->setPage($page)
            ->setTypes($request->getTransactionTypes())
        ;

        $start     = $this->parameters->get('start');
        $end       = $this->parameters->get('end');
        if (null !== $start) {
            $collector->setStart($start);
        }
        if (null !== $end) {
            $collector->setEnd($end);
        }

        $paginator = $collector->getPaginatedGroups();
        $params    = $request->buildParams($pageSize);
        $paginator->setPath(
            sprintf(
                '%s?%s',
                route('api.v2.transactions.list'),
                $params
            )
        );

        return response()
            ->json($this->jsonApiList('transactions', $paginator, new TransactionGroupTransformer()))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
