<?php

/*
 * UpdateController.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V2\Controllers\Model\Account;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Model\Account\UpdateRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public const string RESOURCE_KEY = 'accounts';

    private AccountRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));

                return $next($request);
            }
        );
    }

    /**
     * TODO this endpoint is not yet reachable.
     */
    public function update(UpdateRequest $request, Account $account): JsonResponse
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $data         = $request->getUpdateData();
        $data['type'] = config('firefly.shortNamesByFullName.'.$account->accountType->type);
        $account      = $this->repository->update($account, $data);
        $account->refresh();
        app('preferences')->mark();

        $transformer  = new AccountTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject('accounts', $account, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
