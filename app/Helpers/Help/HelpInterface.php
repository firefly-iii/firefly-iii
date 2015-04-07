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
     * @param $key
     *
     * @return string
     */
    public function getFromCache($key);

    /**
     * @return boolean
     */
    public function hasRoute($route);

    /**
     * @param $route
     *
     * @return array
     */
    public function getFromGithub($route);

    /**
     * @param       $route
     * @param array $content
     *
     * @return void
     */
    public function putInCache($route, array $content);

    /**
     * @param $route
     *
     * @return bool
     */
    public function inCache($route);
}