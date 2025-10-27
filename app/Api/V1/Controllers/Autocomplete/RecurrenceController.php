<?php

/**
 * RecurrenceController.php
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
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class RecurrenceController
 */
class RecurrenceController extends Controller
{
    private RecurringRepositoryInterface $repository;
    protected array $acceptedRoles = [UserRoleEnum::READ_RECURRING];

    /**
     * RecurrenceController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->repository = app(RecurringRepositoryInterface::class);
                $this->repository->setUser($this->user);
                $this->repository->setUserGroup($this->userGroup);

                return $next($request);
            }
        );
    }

    public function recurring(AutocompleteApiRequest $request): JsonResponse
    {
        $recurrences = $this->repository->searchRecurrence($request->attributes->get('query'), $request->attributes->get('limit'));
        $response    = [];

        /** @var Recurrence $recurrence */
        foreach ($recurrences as $recurrence) {
            $response[] = [
                'id'          => (string) $recurrence->id,
                'name'        => $recurrence->title,
                'description' => $recurrence->description,
                'active'      => $recurrence->active,
            ];
        }

        return response()->api($response);
    }
}
