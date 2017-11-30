<?php
/**
 * HelpInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Help;

/**
 * Interface HelpInterface.
 */
interface HelpInterface
{
    /**
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromCache(string $route, string $language): string;

    /**
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromGithub(string $route, string $language): string;

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route): bool;

    /**
     * @param string $route
     * @param string $language
     *
     * @return bool
     */
    public function inCache(string $route, string $language): bool;

    /**
     * @param string $route
     * @param string $language
     * @param string $content
     */
    public function putInCache(string $route, string $language, string $content);
}
