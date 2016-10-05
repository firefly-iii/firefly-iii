<?php
/**
 * FireflyConfig.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;

use Cache;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Preference;
use Log;

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
        $fullName = 'preference' . auth()->user()->id . $name;
        if (Cache::has($fullName)) {
            Cache::forget($fullName);
        }
        Preference::where('user_id', auth()->user()->id)->where('name', $name)->delete();

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
        Log::debug('Now in FFConfig::get()', ['name' => $name]);
        $fullName = 'ff-config-' . $name;
        if (Cache::has($fullName)) {
            Log::debug('Return cache.');

            return Cache::get($fullName);
        }

        $config = Configuration::where('name', $name)->first(['id', 'name', 'data']);

        if ($config) {
            Cache::forever($fullName, $config);
            Log::debug('Return found one.');

            return $config;
        }
        // no preference found and default is null:
        if (is_null($default)) {
            // return NULL
            Log::debug('Return null.');

            return null;
        }

        Log::debug('Return this->set().');

        return $this->set($name, $default);

    }

    /**
     * @param $name
     * @param $value
     *
     * @return Configuration
     */
    public function put($name, $value): Configuration
    {
        return $this->set($name, $value);
    }

    /**
     * @param        $name
     * @param string $value
     *
     * @return Configuration
     */
    public function set($name, $value): Configuration
    {
        Log::debug('Set new value for ', ['name' => $name]);
        $config = Configuration::whereName($name)->first();
        if (is_null($config)) {
            Log::debug('Does not exist yet ', ['name' => $name]);
            $item       = new Configuration;
            $item->name = $name;
            $item->data = $value;
            $item->save();

            Cache::forget('ff-config-' . $name);

            return $item;
        } else {
            Log::debug('Exists already ', ['name' => $name]);
            $config->data = $value;
            $config->save();
            Cache::forget('ff-config-' . $name);

            return $config;
        }

    }

}
