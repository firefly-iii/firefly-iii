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
    // most basic rights, cannot see other members, can see everything else.
    // includes reading of metadata
    case READ_ONLY            = 'ro';

    // required to even USE the group properly (in this order)
    case MANAGE_TRANSACTIONS  = 'mng_trx';

    // required to edit, add or change categories/tags/object-groups
    case MANAGE_META          = 'mng_meta';

    // read other objects and things.
    case READ_BUDGETS       = 'read_budgets';
    case READ_PIGGY_BANKS   = 'read_piggies';
    case READ_SUBSCRIPTIONS = 'read_subscriptions';
    case READ_RULES         = 'read_rules';
    case READ_RECURRING     = 'read_recurring';
    case READ_WEBHOOKS      = 'read_webhooks';
    case READ_CURRENCIES    = 'read_currencies';

    // manage other financial objects:
    case MANAGE_BUDGETS       = 'mng_budgets';
    case MANAGE_PIGGY_BANKS   = 'mng_piggies';
    case MANAGE_SUBSCRIPTIONS = 'mng_subscriptions';
    case MANAGE_RULES         = 'mng_rules';
    case MANAGE_RECURRING     = 'mng_recurring';
    case MANAGE_WEBHOOKS      = 'mng_webhooks';
    case MANAGE_CURRENCIES    = 'mng_currencies';

    // view and generate reports
    case VIEW_REPORTS         = 'view_reports';

    // view memberships AND roles. needs FULL to manage them.
    case VIEW_MEMBERSHIPS     = 'view_memberships';

    // everything the creator can, except remove/change original creator and delete group
    case FULL                 = 'full';

    // reserved for original creator
    case OWNER                = 'owner';
}
