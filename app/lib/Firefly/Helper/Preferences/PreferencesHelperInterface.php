<?php
namespace Firefly\Helper\Preferences;

/**
 * Interface PreferencesHelperInterface
 *
 * @package Firefly\Helper\Preferences
 */
interface PreferencesHelperInterface
{


    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function set($name, $value);

    /**
     * @param      $name
     * @param null $default
     *
     * @return mixed
     */
    public function get($name, $default = null);

}