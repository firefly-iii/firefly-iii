<?php

/*
 * UserRoleEnum.php
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
 * Class UserRoleEnum
 */
enum UserRoleEnum: string
{
    case CHANGE_PIGGY_BANKS  = 'change_piggies';
    case CHANGE_REPETITIONS  = 'change_reps';
    case CHANGE_RULES        = 'change_rules';
    case CHANGE_TRANSACTIONS = 'change_tx';
    case FULL                = 'full';
    case OWNER               = 'owner';
    case READ_ONLY           = 'ro';
    case VIEW_REPORTS        = 'view_reports';
}
