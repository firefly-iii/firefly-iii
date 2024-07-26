<?php
/*
 * AccountBalanceQuery.php
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

namespace FireflyIII\JsonApi\V2\AccountBalances\Capabilities;

use FireflyIII\Entities\AccountBalance;
use FireflyIII\Models\Account;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;

class AccountBalanceQuery extends QueryAll
{
    private Account $account;

    /**
     * QuerySites constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function get(): iterable
    {
        return [
            AccountBalance::fromArray(),
            AccountBalance::fromArray(),
            AccountBalance::fromArray(),
            AccountBalance::fromArray(),
        ];
    }

    public function withAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }
}
