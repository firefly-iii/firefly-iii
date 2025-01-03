<?php

/*
 * EitherConfigKey.php
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

namespace FireflyIII\Support\Binder;

use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EitherConfigKey
 */
class EitherConfigKey
{
    public static array $static
        = [
            // currency conversion
            'cer.enabled',

            // firefly iii settings
            'firefly.version',
            'firefly.default_location',
            'firefly.account_to_transaction',
            'firefly.allowed_opposing_types',
            'firefly.accountRoles',
            'firefly.valid_liabilities',
            'firefly.interest_periods',
            'firefly.bill_periods',
            'firefly.enable_external_map',
            'firefly.expected_source_types',
            'firefly.credit_card_types',
            'firefly.languages',
            'app.timezone',
            'firefly.valid_view_ranges',

            // triggers and actions:
            'firefly.rule-actions',
            'firefly.context-rule-actions',
            'search.operators',
        ];

    /**
     * @throws NotFoundHttpException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public static function routeBinder(string $value, Route $route): string
    {
        if (in_array($value, self::$static, true) || in_array($value, DynamicConfigKey::$accepted, true)) {
            return $value;
        }

        throw new NotFoundHttpException();
    }
}
