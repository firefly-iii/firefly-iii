<?php

declare(strict_types=1);
/*
 * AccountRepository.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Repositories\Administration\Account;

use FireflyIII\Support\Repositories\Administration\AdministrationTrait;
use Illuminate\Support\Collection;

/**
 * Class AccountRepository
 */
class AccountRepository implements AccountRepositoryInterface
{
    use AdministrationTrait;

    /**
     * @inheritDoc
     */
    public function searchAccount(string $query, array $types, int $limit): Collection
    {
        // search by group, not by user
        $dbQuery = $this->userGroup->accounts()
                                   ->where('active', true)
                                   ->orderBy('accounts.order', 'ASC')
                                   ->orderBy('accounts.account_type_id', 'ASC')
                                   ->orderBy('accounts.name', 'ASC')
                                   ->with(['accountType']);
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->where('name', 'LIKE', $search);
            }
        }
        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }
}
