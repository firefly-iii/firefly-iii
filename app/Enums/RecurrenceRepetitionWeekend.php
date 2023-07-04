<?php

/*
 * RecurrenceRepetitionWeekend.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Enums;

/**
 * Class RecurrenceRepetitionWeekend
 */
enum RecurrenceRepetitionWeekend: int
{
    case WEEKEND_DO_NOTHING    = 1;
    case WEEKEND_SKIP_CREATION = 2;
    case WEEKEND_TO_FRIDAY     = 3;
    case WEEKEND_TO_MONDAY     = 4;
}
