<?php

/*
 * NetWorthController.php
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

namespace FireflyIII\Api\V2\Controllers\Summary;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\SingleDateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;

/**
 * Class NetWorthController
 */
class NetWorthController extends Controller
{
    use ValidatesUserGroupTrait;

    private NetWorthInterface          $netWorth;
    private AccountRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->netWorth   = app(NetWorthInterface::class);
                $this->repository = app(AccountRepositoryInterface::class);
                // new way of user group validation
                $userGroup        = $this->validateUserGroup($request);
                $this->netWorth->setUserGroup($userGroup);
                $this->repository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/net-worth/getNetWorth
     */
    public function get(SingleDateRequest $request): JsonResponse
    {
        $date     = $request->getDate();
        $accounts = $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]);

        // filter list on preference of being included.
        $filtered = $accounts->filter(
            function (Account $account) {
                $includeNetWorth = $this->repository->getMetaValue($account, 'include_net_worth');

                return null === $includeNetWorth || '1' === $includeNetWorth;
            }
        );

        // skip accounts that should not be in the net worth
        $result   = $this->netWorth->byAccounts($filtered, $date);

        return response()->api($result);
    }
}
