<?php

/*
 * ShowController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Model\TransactionCurrency;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Transformers\V2\CurrencyTransformer;
use Illuminate\Http\JsonResponse;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    public const string RESOURCE_KEY = 'transaction-currencies';

    private CurrencyRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(CurrencyRepositoryInterface::class);
                // new way of user group validation
                $userGroup        = $this->validateUserGroup($request);
                $this->repository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    public function show(TransactionCurrency $currency): JsonResponse
    {
        $groups                     = $currency->userGroups()->where('user_groups.id', $this->repository->getUserGroup()->id)->get();
        $enabled                    = $groups->count() > 0;
        $default                    = false;

        /** @var UserGroup $group */
        foreach ($groups as $group) {
            $default = 1 === $group->pivot->group_default;
        }
        $currency->userGroupEnabled = $enabled;
        $currency->userGroupNative  = $default;


        $transformer                = new CurrencyTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject(self::RESOURCE_KEY, $currency, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
