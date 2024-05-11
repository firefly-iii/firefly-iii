<?php

namespace FireflyIII\Http\Controllers\Api\V3\Controllers;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\JsonApi\V3\AccountBalances\AccountBalanceSchema;
use FireflyIII\Models\Account;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousQuery;

class AccountController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    public function readAccountBalances(AnonymousQuery $query, AccountBalanceSchema $schema, Account $account): Responsable
    {
        $schema = JsonApi::server()->schemas()->schemaFor('account-balances');

        $models = $schema
            ->repository()
            ->queryAll()
            ->withRequest($query)
            ->withAccount($account)
            ->get();

        return DataResponse::make($models);
    }
}
