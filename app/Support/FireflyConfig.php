<?php
/**
 * FireflyConfig.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;

use Auth;
use Cache;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Preference;

/**
 * Class FireflyConfig
 *
 * @package FireflyIII\Support
 */
class FireflyConfig
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
        Preference::where('user_id', Auth::user()->id)->where('name', $name)->delete();

        return true;
    }

    /**
     * @param      $name
     * @param null $default
     *
     * @return Configuration|null
     */
    public function get($name, $default = null)
    {
        $fullName = 'ff-config-' . $name;
        if (Cache::has($fullName)) {
            return Cache::get($fullName);
        }

        $config = Configuration::where('name', $name)->first(['id', 'name', 'data']);

        if ($config) {
            Cache::forever($fullName, $config);

            return $config;
        }
        // no preference found and default is null:
        if (is_null($default)) {
            // return NULL
            return null;
        }

        return $this->set($name, $default);

    }

    /**
     * @param        $name
     * @param string $value
     *
     * @return Configuration
     */
    public function set($name, $value): Configuration
    {
        $item       = $this->get($name, $value);
        $item->data = $value;
        $item->save();

        Cache::forget('ff-config-' . $name);

        return $item;

    }

}
