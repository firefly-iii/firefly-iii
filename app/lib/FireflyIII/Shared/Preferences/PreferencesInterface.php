<?php
namespace FireflyIII\Shared\Preferences;

/**
 * Interface PreferencesHelperInterface
 *
 * @package FireflyIII\Shared\Preferences
 */
interface PreferencesInterface
{


    /**
     * @param      $name
     * @param null $default
     *
     * @return \Preference
     */
    public function get($name, $default = null);

    /**
     * @param $name
     * @param $value
     *
     * @return null|\Preference
     */
    public function set($name, $value);

}