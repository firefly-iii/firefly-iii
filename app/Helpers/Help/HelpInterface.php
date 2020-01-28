<?php
/**
 * HelpInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Helpers\Help;

/**
 * Interface HelpInterface.
 */
interface HelpInterface
{
    /**
     * Get the help text from cache.
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromCache(string $route, string $language): string;

    /**
     * Get the help text from GitHub.
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromGitHub(string $route, string $language): string;

    /**
     * Is the route a known route?
     *
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route): bool;

    /**
     * Is the help text in cache?
     *
     * @param string $route
     * @param string $language
     *
     * @return bool
     */
    public function inCache(string $route, string $language): bool;

    /**
     * Put the result in cache.
     *
     * @param string $route
     * @param string $language
     * @param string $content
     */
    public function putInCache(string $route, string $language, string $content);
}
