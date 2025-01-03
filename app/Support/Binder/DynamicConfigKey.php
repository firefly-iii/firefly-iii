<?php

/*
 * DynamicConfigKey.php
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
 * Class DynamicConfigKey
 */
class DynamicConfigKey
{
    public static array $accepted
        = [
            'configuration.is_demo_site',
            'configuration.permission_update_check',
            'configuration.single_user_mode',
            'configuration.last_update_check',
        ];

    /**
     * @throws NotFoundHttpException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public static function routeBinder(string $value, Route $route): string
    {
        if (in_array($value, self::$accepted, true)) {
            return $value;
        }

        throw new NotFoundHttpException();
    }
}
