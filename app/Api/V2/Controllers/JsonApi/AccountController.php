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
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

/**
 * Class AccountController
 *
 * This class handles api/v2 requests for accounts.
 * Most stuff is default stuff.
 *
 */
class AccountController extends Controller
{
    use Actions\AttachRelationship;
    use Actions\Destroy;
    use Actions\DetachRelationship;
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\Store;
    use Actions\Update;
    use Actions\UpdateRelationship;


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
