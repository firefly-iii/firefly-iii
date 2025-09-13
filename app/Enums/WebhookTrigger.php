<?php

/*
 * WebhookTrigger.php
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
 * Class WebhookTrigger
 */
enum WebhookTrigger: int
{
    case ANY                       = 50;
    case STORE_TRANSACTION         = 100;
    case UPDATE_TRANSACTION        = 110;
    case DESTROY_TRANSACTION       = 120;
    case STORE_BUDGET              = 200;
    case UPDATE_BUDGET             = 210;
    case DESTROY_BUDGET            = 220;
    case STORE_UPDATE_BUDGET_LIMIT = 230;
}
