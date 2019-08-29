<?php
/**
 * FireflyConfig.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support;

use Cache;
use Exception;
use FireflyIII\Models\Configuration;
use Log;

/**
 * Class FireflyConfig.
 * @codeCoverageIgnore
 */
class FireflyConfig
{

    /**
     * @param string $name
     */
    public function delete(string $name): void
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s("%s") should NOT be called in the TEST environment!', __METHOD__, $name));
        }
        $fullName = 'ff-config-' . $name;
        if (Cache::has($fullName)) {
            Cache::forget($fullName);
        }
        try {
            Configuration::where('name', $name)->delete();
        } catch (Exception $e) {
            Log::debug(sprintf('Could not delete config value: %s', $e->getMessage()));

        }
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return \FireflyIII\Models\Configuration|null
     */
    public function get(string $name, $default = null): ?Configuration
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s("%s") should NOT be called in the TEST environment!', __METHOD__, $name));
        }
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
        if (null === $default) {
            return null;
        }

        return $this->set($name, $default);
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return \FireflyIII\Models\Configuration|null
     */
    public function getFresh(string $name, $default = null): ?Configuration
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $config = Configuration::where('name', $name)->first(['id', 'name', 'data']);
        if ($config) {

            return $config;
        }
        // no preference found and default is null:
        if (null === $default) {
            return null;
        }

        return $this->set($name, $default);
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return Configuration
     */
    public function put(string $name, $value): Configuration
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }

        return $this->set($name, $value);
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return Configuration
     */
    public function set(string $name, $value): Configuration
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        Log::debug('Set new value for ', ['name' => $name]);
        /** @var Configuration $config */
        $config = Configuration::whereName($name)->first();
        if (null === $config) {
            Log::debug('Does not exist yet ', ['name' => $name]);
            /** @var Configuration $item */
            $item       = new Configuration;
            $item->name = $name;
            $item->data = $value;
            $item->save();

            Cache::forget('ff-config-' . $name);

            return $item;
        }
        Log::debug('Exists already, overwrite value.', ['name' => $name]);
        $config->data = $value;
        $config->save();
        Cache::forget('ff-config-' . $name);

        return $config;
    }
}
