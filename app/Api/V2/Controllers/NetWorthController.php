<?php
declare(strict_types=1);
/*
 * NetWorthController.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers;

use FireflyIII\Api\V2\Request\Generic\SingleDateRequest;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ConvertsExchangeRates;
use Illuminate\Http\JsonResponse;

/**
 * Class NetWorthController
 */
class NetWorthController extends Controller
{
    use ConvertsExchangeRates;

    private NetWorthInterface          $netWorth;
    private AccountRepositoryInterface $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->netWorth   = app(NetWorthInterface::class);
                $this->netWorth->setUser(auth()->user());
                return $next($request);
            }
        );
    }

    /**
     * @param SingleDateRequest $request
     * @return JsonResponse
     */
    public function get(SingleDateRequest $request): JsonResponse
    {
        $date      = $request->getDate();
        $result    = $this->netWorth->sumNetWorthByCurrency($date);
        $converted = $this->cerSum($result);

        return response()->api($converted);
    }

}
