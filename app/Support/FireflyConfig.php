<?php
/**
 * FireflyConfig.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support;

use Cache;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use Illuminate\Database\QueryException;
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
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return Configuration::where('name', $name)->count() === 1;
    }

    /**
     * @param string $name
     * @param null   $default
     *
     * @throws FireflyException
     * @return Configuration|null
     */
    public function get(string $name, $default = null): ?Configuration
    {
        $fullName = 'ff-config-' . $name;
        if (Cache::has($fullName)) {
            return Cache::get($fullName);
        }

        try {
            /** @var Configuration $config */
            $config = Configuration::where('name', $name)->first(['id', 'name', 'data']);
        } catch (QueryException|Exception $e) {
            throw new FireflyException(sprintf('Could not poll the database: %s', $e->getMessage()));
        }

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

        return $this->set($name, $value);
    }

    /**
     * @param string $name
     * @param $value
     * @param int|string|true $value
     *
     * @return Configuration
     */
    public function set(string $name, $value): Configuration
    {
        /** @var Configuration $config */
        try {
            $config = Configuration::whereName($name)->first();
        } catch (QueryException|Exception $e) {
            $item       = new Configuration;
            $item->name = $name;
            $item->data = $value;

            return $item;
        }
        if (null === $config) {
            /** @var Configuration $item */
            $item       = new Configuration;
            $item->name = $name;
            $item->data = $value;
            $item->save();

            Cache::forget('ff-config-' . $name);

            return $item;
        }
        $config->data = $value;
        $config->save();
        Cache::forget('ff-config-' . $name);

        return $config;
    }
}
