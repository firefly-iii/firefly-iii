<?php


/*
 * StoreController.php
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

namespace FireflyIII\Api\V2\Controllers\Model\Transaction;

use FireflyIII\Api\V2\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    /**
     * TODO this method is practically the same as the V1 method and borrows as much code as possible.
     * TODO still it duplicates a lot.
     * TODO the v1 endpoints will never support separate administrations, this is an important distinction.
     *
     * @return JsonResponse
     */
    public function post(): JsonResponse
    {

        return response()->json([]);

    }


}
