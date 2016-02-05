<?php

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
    public function getFromCache(string $key);

    /**
     * @param string $route
     *
     * @return array
     */
    public function getFromGithub(string $route);

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route);

    /**
     * @param string $route
     *
     * @return bool
     */
    public function inCache(string $route);

    /**
     * @param string $route
     * @param array  $content
     *
     * @return void
     */
    public function putInCache(string $route, array $content);
}
