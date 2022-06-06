<?php
/*
 * SumController.php
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

namespace FireflyIII\Api\V2\Controllers\Model\Bill;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\Http\Api\ConvertsExchangeRates;
use Illuminate\Http\JsonResponse;

/**
 * Class SumController
 */
class SumController extends Controller
{
    private BillRepositoryInterface $repository;
    use ConvertsExchangeRates;

    /**
     *
     */
    public function __construct()
    {
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BillRepositoryInterface::class);
                return $next($request);
            }
        );
    }

    /**
     * @param DateRequest $request
     * @return JsonResponse
     */
    public function unpaid(DateRequest $request): JsonResponse
    {
        $dates     = $request->getAll();
        $result    = $this->repository->sumUnpaidInRange($dates['start'], $dates['end']);
        $converted = $this->cerSum($result);

        // convert to JSON response:
        return response()->json($converted);
    }

    /**
     * @param DateRequest $request
     * @return JsonResponse
     */
    public function paid(DateRequest $request): JsonResponse
    {
        $dates     = $request->getAll();
        $result    = $this->repository->sumPaidInRange($dates['start'], $dates['end']);
        $converted = $this->cerSum($result);

        // convert to JSON response:
        return response()->json($converted);
    }
}
