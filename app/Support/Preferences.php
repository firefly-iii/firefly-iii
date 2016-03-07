<?php
declare(strict_types = 1);

namespace FireflyIII\Support;

use Auth;
use Cache;
use FireflyIII\Models\Preference;

/**
 * Class Preferences
 *
 * @package FireflyIII\Support
 */
class Preferences
{
    /**
     * @param $name
     *
     * @return bool
     * @throws \Exception
     */
    public function delete($name): bool
    {
        $fullName = 'preference' . Auth::user()->id . $name;
        if (Cache::has($fullName)) {
            Cache::forget($fullName);
        }
        /** @var Preference $preference */
        $preference = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first();
        $preference->delete();

        return true;
    }

    /**
     * @param      string $name
     * @param string      $default
     *
     * @return null|\FireflyIII\Models\Preference
     */
    public function get($name, $default = null)
    {
        $fullName = 'preference' . Auth::user()->id . $name;
        if (Cache::has($fullName)) {
            return Cache::get($fullName);
        }

        $preference = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first(['id', 'name', 'data_encrypted']);

        if ($preference) {
            Cache::forever($fullName, $preference);

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
     * @return string
     */
    public function lastActivity()
    {
        $preference = $this->get('lastActivity', microtime())->data;

        return md5($preference);
    }

    /**
     * @return bool
     */
    public function mark()
    {
        $this->set('lastActivity', microtime());

        return true;
    }

    /**
     * @param        $name
     * @param string $value
     *
     * @return Preference
     */
    public function set($name, $value)
    {
        $fullName = 'preference' . Auth::user()->id . $name;
        Cache::forget($fullName);
        $pref = Preference::where('user_id', Auth::user()->id)->where('name', $name)->first(['id', 'name', 'data_encrypted']);
        if ($pref) {
            $pref->data = $value;
        } else {
            $pref       = new Preference;
            $pref->name = $name;
            $pref->data = $value;
            $pref->user()->associate(Auth::user());

        }
        $pref->save();

        Cache::forever($fullName, $pref);

        return $pref;

    }
}
