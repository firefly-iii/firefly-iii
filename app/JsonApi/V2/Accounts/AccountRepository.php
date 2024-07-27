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
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\NonEloquent\AbstractRepository;
use LaravelJsonApi\NonEloquent\Capabilities\CrudRelations;
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
    use UsergroupAware;
    use HasRelationsCapability;
    /**
     * SiteRepository constructor.
     */
    public function __construct() {}

    public function find(string $resourceId): ?object
    {
        return Account::find((int) $resourceId);
    }

    public function queryAll(): Capabilities\AccountQuery
    {
        return Capabilities\AccountQuery::make()
            ->withUserGroup($this->userGroup)
            ->withServer($this->server)
            ->withSchema($this->schema)
        ;
    }

    /**
     * @inheritDoc
     */
    protected function relations(): CrudRelations
    {
        return Capabilities\CrudAccountRelations::make();
    }
}
