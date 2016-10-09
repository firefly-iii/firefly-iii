<?php
/**
 * HelpInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Help;

/**
 * Interface HelpInterface
 *
 * @package FireflyIII\Helpers\Help
 */
interface HelpInterface
{

    /**
     * @param string $key
     *
     * @return string
     */
    public function getFromCache(string $key): string;

    /**
     * @param string $language
     * @param string $route
     *
     * @return array
     */
    public function getFromGithub(string $language, string $route):array;

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route): bool;

    /**
     * @param string $route
     *
     * @return bool
     */
    public function inCache(string $route): bool;

    /**
     * @param string $route
     * @param string $language
     * @param array  $content
     */
    public function putInCache(string $route, string $language, array $content);
}
