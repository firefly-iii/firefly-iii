<?php
namespace FireflyIII\Shared\Preferences;

/**
 * Interface PreferencesHelperInterface
 *
 * @package Firefly\Helper\Preferences
 */
interface PreferencesInterface
{


    /**
     * @param $name
     * @param $value
     *
     * @return null|\Preference
     */
    public function set($name, $value);

    /**
     * @param      $name
     * @param null $default
     *
     * @return \Preference
     */
    public function get($name, $default = null);

}