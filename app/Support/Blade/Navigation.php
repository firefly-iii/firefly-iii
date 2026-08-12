<?php




declare(strict_types=1);



namespace FireflyIII\Support\Blade;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class Navigation
{
    public static function menuItemActive(string $route): string
    {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuItemActive("%s" = "%s")', $route, $name));
        if ($name === $route) {
            return 'active';
        }

        return '';
    }

    public static function menuItemActivePartial(string $route): string
    {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuItemActivePartial("%s" starts with "%s")', $name, $route));
        if (str_starts_with($name, $route)) {
            return 'active';
        }

        return '';
    }

    public static function menuOpenPartial(string $route): string
    {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuOpenPartial("%s" starts with "%s")', $name, $route));
        if (str_starts_with($name, $route)) {
            return 'menu-open';
        }

        return '';
    }

    public static function menuSubItemActive(string $route, string $objectType): string
    {
        $name = Route::getCurrentRoute()->getName() ?? '';
        Log::debug(sprintf('menuSubItemActive("%s" = "%s","%s" = "%s")', $route, $name, $objectType, Route::getCurrentRoute()->parameter('objectType')));
        if ($name === $route && $objectType === Route::getCurrentRoute()->parameter('objectType')) {
            return 'active';
        }

        return '';
    }
}
