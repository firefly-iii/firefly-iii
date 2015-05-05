<?php

namespace FireflyIII\Support;

use Auth;
use FireflyIII\Models\Preference;

/**
 * Class Preferences
 *
 * @package FireflyIII\Support
 */
class Preferences
{
    /**
     * @param      $name
     * @param null $default
     *
     * @return null|\FireflyIII\Models\Preference
     */
    public function get($name, $default = null)
    {
        $pref = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first();
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
     * @return Preference
     */
    public function set($name, $value)
    {
        $pref = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first();
        if (is_null($pref)) {
            $pref       = new Preference;
            $pref->name = $name;
        }
        $pref->data = $value;
        if (!is_null(Auth::user()->id)) {
            $pref->user()->associate(Auth::user());
            $pref->save();
        }

        return $pref;

    }
}
