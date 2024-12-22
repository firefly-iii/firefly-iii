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
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Class FireflyConfig.
 */
class FireflyConfig
{
    public function delete(string $name): void
    {
        $fullName = 'ff-config-' . $name;
        if (Cache::has($fullName)) {
            Cache::forget($fullName);
        }
        Configuration::where('name', $name)->forceDelete();
    }

    public function has(string $name): bool
    {
        return 1 === Configuration::where('name', $name)->count();
    }

    public function getEncrypted(string $name, $default = null): ?Configuration
    {
        $result = $this->get($name, $default);
        if (null === $result) {
            return null;
        }
        if ('' === $result->data) {
            Log::warning(sprintf('Empty encrypted configuration value found: "%s"', $name));

            return $result;
        }

        try {
            $result->data = decrypt($result->data);
        } catch (DecryptException $e) {
            Log::error(sprintf('Could not decrypt configuration value "%s": %s', $name, $e->getMessage()));

            return $result;
        }

        return $result;
    }

    /**
     * @param null|bool|int|string $default
     *
     * @throws FireflyException
     */
    public function get(string $name, $default = null): ?Configuration
    {
        $fullName = 'ff-config-' . $name;
        if (Cache::has($fullName)) {
            return Cache::get($fullName);
        }

        try {
            /** @var null|Configuration $config */
            $config = Configuration::where('name', $name)->first(['id', 'name', 'data']);
        } catch (Exception | QueryException $e) {
            throw new FireflyException(sprintf('Could not poll the database: %s', $e->getMessage()), 0, $e);
        }

        if (null !== $config) {
            Cache::forever($fullName, $config);

            return $config;
        }
        // no preference found and default is null:
        if (null === $default) {
            return null;
        }

        return $this->set($name, $default);
    }

    public function set(string $name, mixed $value): Configuration
    {
        try {
            $config = Configuration::whereName($name)->whereNull('deleted_at')->first();
        } catch (QueryException $e) {
            app('log')->error($e->getMessage());
            $item       = new Configuration();
            $item->name = $name;
            $item->data = $value;

            return $item;
        }

        if (null === $config) {
            $item       = new Configuration();
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

    /**
     * @param mixed $default
     */
    public function getFresh(string $name, $default = null): ?Configuration
    {
        $config = Configuration::where('name', $name)->first(['id', 'name', 'data']);
        if (null !== $config) {
            return $config;
        }
        // no preference found and default is null:
        if (null === $default) {
            return null;
        }

        return $this->set($name, $default);
    }

    /**
     * @param mixed $value
     */
    public function put(string $name, $value): Configuration
    {
        return $this->set($name, $value);
    }

    public function setEncrypted(string $name, mixed $value): Configuration
    {
        try {
            $encrypted = encrypt($value);
        } catch (EncryptException $e) {
            Log::error(sprintf('Could not encrypt configuration value "%s": %s', $name, $e->getMessage()));

            throw new FireflyException(sprintf('Could not encrypt configuration value "%s". Cowardly refuse to continue.', $name));
        }

        return $this->set($name, $encrypted);
    }
}
