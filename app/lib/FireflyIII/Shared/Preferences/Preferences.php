<?php
namespace FireflyIII\Shared\Preferences;
/**
 * Class PreferencesHelper
 *
 * @package FireflyIII\Shared\Preferences
 */
class Preferences implements PreferencesInterface
{

    /**
     * @param      $name
     * @param null $default
     *
     * @return null|\Preference
     */
    public function get($name, $default = null)
    {
        $pref = \Preference::where('user_id', \Auth::user()->id)->where('name', $name)->first();
        if (is_null($pref) && is_null($default)) {
            // return NULL
            return null;
        }
        if (!is_null($pref)) {
            return $pref;
        }

        return $this->set($name, $default);

    }

    /**
     * @param $name
     * @param $value
     *
     * @return \Preference
     */
    public function set($name, $value)
    {
        $pref = \Preference::where('user_id', \Auth::user()->id)->where('name', $name)->first();
        if (is_null($pref)) {
            $pref       = new \Preference;
            $pref->name = $name;
            $pref->user()->associate(\Auth::user());

        }
        $pref->data = $value;
        $pref->save();


        return $pref;

    }
}
