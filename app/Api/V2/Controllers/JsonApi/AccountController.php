<?php

/*
 * AccountController.php
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

namespace FireflyIII\Api\V2\Controllers\JsonApi;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\JsonApi\V2\Accounts\AccountCollectionQuery;
use FireflyIII\JsonApi\V2\Accounts\AccountSchema;
use FireflyIII\JsonApi\V2\Accounts\AccountSingleQuery;
use FireflyIII\Models\Account;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

/**
 * Class AccountController
 *
 * This class handles api/v2 requests for accounts.
 * Most stuff is default stuff.
 */
class AccountController extends Controller
{
    use Actions\AttachRelationship;
    use Actions\Destroy;
    use Actions\DetachRelationship;

    use Actions\FetchMany;

    // use Actions\FetchOne;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\Store;
    use Actions\Update;
    use Actions\UpdateRelationship;

    /**
     * Fetch zero to many JSON API resources.
     *
     * @return Responsable|Response
     */
    public function index(AccountSchema $schema, AccountCollectionQuery $request)
    {
        Log::debug(__METHOD__);
        $models = $schema
            ->repository()
            ->queryAll()
            ->withRequest($request)
            ->get()
        ;

        // do something custom...

        return new DataResponse($models);
    }

    /**
     * Fetch zero to one JSON API resource by id.
     *
     * @return Responsable|Response
     */
    public function show(AccountSchema $schema, AccountSingleQuery $request, Account $account)
    {
        Log::debug(__METHOD__);
        $model = $schema->repository()
            ->queryOne($account)
            ->withRequest($request)
            ->first()
        ;
        Log::debug(sprintf('%s again!', __METHOD__));

        // do something custom...

        return new DataResponse($model);
    }

    //    public function readAccountBalances(AnonymousQuery $query, AccountBalanceSchema $schema, Account $account): Responsable
    //    {
    //        $schema = JsonApi::server()->schemas()->schemaFor('account-balances');
    //
    //        $models = $schema
    //            ->repository()
    //            ->queryAll()
    //            ->withRequest($query)
    //            ->withAccount($account)
    //            ->get()
    //        ;
    //
    //        return DataResponse::make($models);
    //    }
}
