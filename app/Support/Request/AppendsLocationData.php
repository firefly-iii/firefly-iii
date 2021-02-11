<?php
/**
 * AppendsLocationData.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Request;

use Log;

/**
 * Trait AppendsLocationData
 */
trait AppendsLocationData
{
    /**
     * Abstract method stolen from "InteractsWithInput".
     *
     * @param null $key
     * @param bool $default
     *
     * @return mixed
     */
    abstract public function boolean($key = null, $default = false);

    /**
     * Abstract method.
     *
     * @param $key
     *
     * @return bool
     */
    abstract public function has($key);

    /**
     * Abstract method.
     *
     * @return string
     */
    abstract public function method();

    /**
     * Abstract method.
     *
     * @param mixed ...$patterns
     *
     * @return mixed
     */
    abstract public function routeIs(...$patterns);

    /**
     * Read the submitted Request data and add new or updated Location data to the array.
     *
     * @param array       $data
     *
     * @param string|null $prefix
     *
     * @return array
     */
    protected function appendLocationData(array $data, ?string $prefix): array
    {
        Log::debug(sprintf('Now in appendLocationData("%s")', $prefix), $data);
        $data['store_location']  = false;
        $data['update_location'] = false;
        $data['longitude']       = null;
        $data['latitude']        = null;
        $data['zoom_level']      = null;

        $longitudeKey   = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey    = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey   = $this->getLocationKey($prefix, 'zoom_level');
        $hasLocationKey = $this->getLocationKey($prefix, 'has_location');
        $hasLocation    = $this->boolean($hasLocationKey) || true === ($data['has_location'] ?? false);

        // for a POST (store), all fields must be present and accounted for:
        if (
            ('POST' === $this->method() && $this->routeIs('*.store'))
            && ($this->has($longitudeKey) && $this->has($latitudeKey) && $this->has($zoomLevelKey))
        ) {
            Log::debug('Method is POST and all fields present.');
            $data['store_location'] = $this->boolean($hasLocationKey);
            $data['longitude']      = $this->nullableString($longitudeKey);
            $data['latitude']       = $this->nullableString($latitudeKey);
            $data['zoom_level']     = $this->nullableString($zoomLevelKey);
        }
        if (
            ($this->has($longitudeKey) && $this->has($latitudeKey) && $this->has($zoomLevelKey))
            && (
                ('PUT' === $this->method() && $this->routeIs('*.update'))
                || ('POST' === $this->method() && $this->routeIs('*.update'))
            )
        ) {
            Log::debug('Method is PUT and all fields present.');
            $data['update_location'] = true;
            $data['longitude']       = $this->nullableString($longitudeKey);
            $data['latitude']        = $this->nullableString($latitudeKey);
            $data['zoom_level']      = $this->nullableString($zoomLevelKey);
        }
        if (false === $hasLocation || null === $data['longitude'] || null === $data['latitude'] || null === $data['zoom_level']) {
            Log::debug('One of the fields is NULL or hasLocation is false, wont save.');
            Log::debug(sprintf('Longitude   : %s', var_export($data['longitude'], true)));
            Log::debug(sprintf('Latitude    : %s', var_export($data['latitude'], true)));
            Log::debug(sprintf('Zoom level  : %s', var_export($data['zoom_level'], true)));
            Log::debug(sprintf('Has location: %s', var_export($hasLocation, true)));
            $data['store_location']  = false;
            $data['update_location'] = true; // update is always true, but the values are null:
            $data['longitude']       = null;
            $data['latitude']        = null;
            $data['zoom_level']      = null;
        }
        Log::debug(sprintf('Returning longitude: "%s", latitude: "%s", zoom level: "%s"', $data['longitude'], $data['latitude'], $data['zoom_level']));

        return $data;
    }

    /**
     * Abstract method to ensure filling later.
     *
     * @param string $field
     *
     * @return string|null
     */
    abstract protected function nullableString(string $field): ?string;

    /**
     * @param string|null $prefix
     * @param string      $key
     *
     * @return string
     */
    private function getLocationKey(?string $prefix, string $key): string
    {
        if (null === $prefix) {
            return $key;
        }

        return sprintf('%s_%s', $prefix, $key);
    }

}
