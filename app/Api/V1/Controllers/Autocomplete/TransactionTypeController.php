<?php

/**
 * TransactionTypeController.php
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
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionTypeController
 */
class TransactionTypeController extends Controller
{
    private TransactionTypeRepositoryInterface $repository;
    protected array $acceptedRoles = [UserRoleEnum::READ_ONLY];

    /**
     * TransactionTypeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->repository = app(TransactionTypeRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    public function transactionTypes(AutocompleteApiRequest $request): JsonResponse
    {
        $types = $this->repository->searchTypes($request->attributes->get('query'), $request->attributes->get('limit'));
        $array = [];

        /** @var TransactionType $type */
        foreach ($types as $type) {
            // different key for consistency.
            $array[] = [
                'id'   => (string) $type->id,
                'name' => $type->type,
                'type' => $type->type,
            ];
        }

        return response()->api($array);
    }
}
