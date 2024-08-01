<?php
/*
 * AccountRepository.php
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

namespace FireflyIII\JsonApi\V2\Accounts;

use FireflyIII\Models\Account;
use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\NonEloquent\AbstractRepository;
use LaravelJsonApi\NonEloquent\Capabilities\CrudRelations;
use LaravelJsonApi\NonEloquent\Capabilities\CrudResource;
use LaravelJsonApi\NonEloquent\Concerns\HasCrudCapability;
use LaravelJsonApi\NonEloquent\Concerns\HasRelationsCapability;

/**
 * Class AccountRepository
 *
 * The repository collects a single or many (account) objects from the database and returns them to the
 * account resource. The account resource links all account properties to the JSON properties.
 *
 * For the queryAll thing, a separate query is constructed that does the actual querying of the database.
 * This is necessary because the user can't just query all accounts (it would return other user's data)
 * and because we also need to collect all kinds of metadata, like the currency and user info.
 */
class AccountRepository extends AbstractRepository implements QueriesAll
{
    use HasCrudCapability;
    use HasRelationsCapability;
    use UsergroupAware;

    /**
     * SiteRepository constructor.
     */
    public function __construct() {
        Log::debug(__METHOD__);
    }

    public function exists(string $resourceId): bool
    {
        $result = null !== Account::find((int) $resourceId);
        Log::debug(sprintf('%s: %s',__METHOD__, var_export($result, true)));

        return $result;
    }

    public function find(string $resourceId): ?object
    {
        die(__METHOD__);
        Log::debug(__METHOD__);
        //        throw new \RuntimeException('trace me');
        $account    = Account::find((int) $resourceId);
        if (null === $account) {
            return null;
        }
        // enrich the collected data
        $enrichment = new AccountEnrichment();

        return $enrichment->enrichSingle($account);
    }

    public function queryAll(): Capabilities\AccountQuery
    {
        Log::debug(__METHOD__);

        return Capabilities\AccountQuery::make()
            ->withUserGroup($this->userGroup)
            ->withServer($this->server)
            ->withSchema($this->schema)
        ;
    }

    protected function crud(): Capabilities\CrudAccount
    {
        Log::debug(__METHOD__);
        return Capabilities\CrudAccount::make();
    }

    protected function relations(): CrudRelations
    {
        Log::debug(__METHOD__);
        return Capabilities\CrudAccountRelations::make();
    }
}
