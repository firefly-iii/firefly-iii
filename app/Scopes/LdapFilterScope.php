<?php

/*
 * LdapFilterScope.php
 * Copyright (c) 2021 james@firefly-iii.org
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

declare(strict_types=1);

namespace FireflyIII\Scopes;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class LdapFilterScope implements ScopeInterface {
    /**
     * If the ADLDAP_AUTH_FILTER is provided, apply the filter to the LDAP query.
     * @param Builder $query
     * @return void
     */
    public function apply(Builder $query)
    {
        $filter = (string) config('ldap_auth.custom_filter');
        if ( '' !== $filter ) {
            $query->rawFilter($filter);
        }
    }
}
