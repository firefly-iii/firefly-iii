<?php

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

declare(strict_types=1);

namespace FireflyIII\Helpers\Collector\Extensions;

use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait CollectorProperties
 */
trait CollectorProperties
{
    private array   $fields;
    private bool    $hasAccountInfo;
    private bool    $hasBillInformation;
    private bool    $hasBudgetInformation;
    private bool    $hasCatInformation;
    private bool    $hasJoinedAttTables;
    private bool    $hasJoinedMetaTables;
    private bool    $hasJoinedTagTables;
    private bool    $hasNotesInformation;
    private array   $integerFields;
    private ?int    $limit;
    private ?int    $page;
    private array   $postFilters;
    private HasMany $query;
    private int     $total;
    private ?User   $user;
}
