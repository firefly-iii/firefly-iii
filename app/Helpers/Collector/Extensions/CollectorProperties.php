<?php
declare(strict_types=1);
/**
 * CollectorProperties.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Helpers\Collector\Extensions;

use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait CollectorProperties
 */
trait CollectorProperties
{
    /** @var array The standard fields to select. */
    private $fields;
    /** @var bool Will be set to true if query result contains account information. (see function withAccountInformation). */
    private $hasAccountInfo;
    /** @var bool Will be true if query result includes bill information. */
    private $hasBillInformation;
    /** @var bool Will be true if query result contains budget info. */
    private $hasBudgetInformation;
    /** @var bool Will be true if query result contains category info. */
    private $hasCatInformation;
    /** @var bool Will be true for attachments */
    private $hasJoinedAttTables;
    /** @var bool Will be true of the query has the tag info tables joined. */
    private $hasJoinedTagTables;
    /** @var array */
    private $integerFields;
    /** @var int The maximum number of results. */
    private $limit;
    /** @var int The page to return. */
    private $page;
    /** @var HasMany The query object. */
    private $query;
    /** @var int Total number of results. */
    private $total;
    /** @var User The user object. */
    private $user;
}
