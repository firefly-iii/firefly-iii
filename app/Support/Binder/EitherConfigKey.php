<?php
/*
 * StaticConfigKey.php
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

namespace FireflyIII\Support\Binder;


use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EitherConfigKey
 */
class EitherConfigKey
{
    public static array $static = [
        'firefly.version',
        'firefly.api_version',
        'firefly.default_location',
        'firefly.account_to_transaction',
        'firefly.allowed_opposing_types',
    ];
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return string
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): string
    {
        if (in_array($value, self::$static, true) || in_array($value, DynamicConfigKey::$accepted, true)) {
            return $value;
        }
        throw new NotFoundHttpException;
    }
}