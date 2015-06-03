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
     * @return string
     */
    public function lastActivity()
    {
        $preference = $this->get('lastActivity', microtime())->data;

        return md5($preference);
    }

    /**
     * @param      $name
     * @param null $default
     *
     * @return null|\FireflyIII\Models\Preference
     */
    public function get($name, $default = null)
    {
        $preference = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first(['id','name','data_encrypted']);

        if ($preference) {
            return $preference;
        }
        // no preference found and default is null:
        if (is_null($default)) {
            // return NULL
            return null;
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
        $pref = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first(['id','name','data_encrypted']);
        if ($pref) {
            $pref->data = $value;
        } else {
            $pref       = new Preference;
            $pref->name = $name;
            $pref->data = $value;
            $pref->user()->associate(Auth::user());

        }
        $pref->save();

        return $pref;

    }

    /**
     * @return bool
     */
    public function mark()
    {
        $this->set('lastActivity', microtime());

        return true;
    }
}
