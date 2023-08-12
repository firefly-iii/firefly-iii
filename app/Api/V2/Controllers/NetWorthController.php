<?php

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

declare(strict_types=1);

namespace FireflyIII\Api\V2\Controllers;

use FireflyIII\Api\V2\Request\Generic\SingleDateRequest;
use FireflyIII\Helpers\Report\NetWorthInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class NetWorthController
 */
class NetWorthController extends Controller
{
    private NetWorthInterface $netWorth;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->netWorth = app(NetWorthInterface::class);
                $this->netWorth->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/net-worth/getNetWorth
     *
     * @param SingleDateRequest $request
     *
     * @return JsonResponse
     */
    public function get(SingleDateRequest $request): JsonResponse
    {
        die('deprecated use of thing.');
        $date      = $request->getDate();
        $result    = $this->netWorth->sumNetWorthByCurrency($date);
        $converted = $this->cerSum($result);

        return response()->api($converted);
    }
}
