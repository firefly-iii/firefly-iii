<?php

/*
 * AccountController.php
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

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Transaction\ListRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use TransactionFilter;

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/accounts/listTransactionByAccount
     */
    public function list(ListRequest $request, Account $account): JsonResponse
    {
        // collect transactions:
        $page      = $request->getPage();
        $page      = max($page, 1);
        $pageSize  = $this->parameters->get('limit');

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))
            ->withAPIInformation()
            ->setLimit($pageSize)
            ->setPage($page)
            ->setTypes($request->getTransactionTypes())
        ;

        $start     = $request->getStartDate();
        $end       = $request->getEndDate();
        if ($start instanceof Carbon) {
            app('log')->debug(sprintf('Set start date to %s', $start->toIso8601String()));
            $collector->setStart($start);
        }
        if ($end instanceof Carbon) {
            app('log')->debug(sprintf('Set end date to %s', $start->toIso8601String()));
            $collector->setEnd($end);
        }

        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(
            sprintf(
                '%s?%s',
                route('api.v2.accounts.transactions', [$account->id]),
                $request->buildParams($pageSize)
            )
        );

        return response()
            ->json($this->jsonApiList('transactions', $paginator, new TransactionGroupTransformer()))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
