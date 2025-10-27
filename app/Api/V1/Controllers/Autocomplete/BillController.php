<?php

/**
 * BillController.php
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
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class BillController
 */
class BillController extends Controller
{
    private BillRepositoryInterface $repository;
    protected array $acceptedRoles = [UserRoleEnum::READ_SUBSCRIPTIONS];

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUser($this->user);
                $this->repository->setUserGroup($this->userGroup);

                return $next($request);
            }
        );
    }

    /**
     * Documentation for this endpoint is at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getBillsAC
     */
    public function bills(AutocompleteApiRequest $request): JsonResponse
    {
        $result   = $this->repository->searchBill($request->attributes->get('query'), $request->attributes->get('limit'));
        $filtered = $result->map(
            static fn (Bill $item) => [
                'id'     => (string) $item->id,
                'name'   => $item->name,
                'active' => $item->active,
            ]
        );

        return response()->api($filtered->toArray());
    }
}
