<?php
/*
 * api.php
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

use FireflyIII\Models\Account;

return [
    // allowed filters (search) for APIs
    'filters'             => [
        'allowed' => [
            'accounts' => [
                'name'               => 'string',
                'active'             => 'boolean',
                'iban'               => 'iban',
                'balance'            => 'numeric',
                'last_activity'      => 'date',
                'balance_difference' => 'numeric',
            ],
        ],
    ],

    // allowed sort columns for APIs
    'sorting'             => [
        'allowed' => [
            'transactions' => ['description', 'amount'],
            'accounts'     => ['name', 'active', 'iban', 'order', 'account_number', 'balance', 'last_activity', 'balance_difference', 'current_debt'],
        ],
    ],
    // valid query columns for sorting the query
    'valid_query_sort'    => [
        Account::class => ['id', 'name', 'active', 'iban', 'order'],
    ],
    // valid query columns for sorting the query results
    'valid_api_sort'      => [
        Account::class => ['account_number'],
    ],
    'full_data_set'       => [
        Account::class => ['last_activity', 'balance', 'balance_difference', 'current_debt', 'account_number'],
    ],
    'valid_query_filters' => [
        Account::class => ['id', 'name', 'iban', 'active'],
    ],
    'valid_api_filters'   => [
        Account::class => ['id', 'name', 'iban', 'active', 'type'],
    ],
];
