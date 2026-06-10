<?php
/*
 * Navigation.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Support\Blade;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class Navigation
{
    public static function menuItemActive (string $route): string {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuItemActive("%s" = "%s")', $route, $name));
        if ($name === $route) {
            return 'active';
        }

        return '';
    }
    public static function menuItemActivePartial(string $route): string {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuItemActivePartial("%s" starts with "%s")', $name, $route));
        if (str_starts_with($name, $route)) {
            return 'active';
        }

        return '';
    }

    public static function menuSubItemActive(string $route, string $objectType): string {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuSubItemActive("%s" = "%s","%s" = "%s")', $route, $name, $objectType, Route::getCurrentRoute()->parameter('objectType')));
        if ($name === $route && $objectType === Route::getCurrentRoute()->parameter('objectType')) {
            return 'active';
        }

        return '';
    }

    public static function menuOpenPartial(string $route): string {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuOpenPartial("%s" starts with "%s")', $name, $route));
        if (str_starts_with($name, $route)) {
            return 'menu-open';
        }

        return '';
    }
}
