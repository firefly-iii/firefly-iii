<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Api\V2\Controllers;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\JsonApi\V2\AccountBalances\AccountBalanceSchema;
use FireflyIII\Models\Account;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousQuery;

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
