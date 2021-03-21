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
        $data['remove_location'] = false;
        $data['longitude']       = null;
        $data['latitude']        = null;
        $data['zoom_level']      = null;

        $longitudeKey    = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey     = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey    = $this->getLocationKey($prefix, 'zoom_level');
        $isValidPOST     = $this->isValidPost($prefix);
        $isValidPUT      = $this->isValidPUT($prefix);
        $isValidEmptyPUT = $this->isValidEmptyPUT($prefix);

        // for a POST (store), all fields must be present and not NULL.
        if ($isValidPOST) {
            Log::debug('Method is POST and all fields present and not NULL.');
            $data['store_location'] = true;
            $data['longitude']      = $this->string($longitudeKey);
            $data['latitude']       = $this->string($latitudeKey);
            $data['zoom_level']     = $this->string($zoomLevelKey);
        }

        // for a PUT (api update) or POST update (UI)
        if ($isValidPUT) {
            Log::debug('Method is PUT and all fields present and not NULL.');
            $data['update_location'] = true;
            $data['longitude']       = $this->string($longitudeKey);
            $data['latitude']        = $this->string($latitudeKey);
            $data['zoom_level']      = $this->string($zoomLevelKey);
        }
        if ($isValidEmptyPUT) {
            Log::debug('Method is PUT and all fields present and NULL.');
            $data['remove_location'] = true;
        }
        Log::debug(sprintf('Returning longitude: "%s", latitude: "%s", zoom level: "%s"', $data['longitude'], $data['latitude'], $data['zoom_level']));
        Log::debug(
            sprintf(
                'Returning actions: store: %s, update: %s, delete: %s',
                var_export($data['store_location'], true),
                var_export($data['update_location'], true),
                var_export($data['remove_location'], true),
            )
        );

        return $data;
    }

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

    /**
     * @param string|null $prefix
     *
     * @return bool
     */
    private function isValidPOST(?string $prefix): bool
    {
        Log::debug('Now in isValidPOST()');
        $longitudeKey   = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey    = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey   = $this->getLocationKey($prefix, 'zoom_level');
        $hasLocationKey = $this->getLocationKey($prefix, 'has_location');
        // fields must not be null:
        if (null !== $this->get($longitudeKey) && null !== $this->get($latitudeKey) && null !== $this->get($zoomLevelKey)) {
            Log::debug('All fields present');
            // if is POST and route contains API, this is enough:
            if ('POST' === $this->method() && $this->routeIs('api.v1.*')) {
                Log::debug('Is API location');

                return true;
            }
            // if is POST and route does not contain API, must also have "has_location" = true
            if ('POST' === $this->method() && $this->routeIs('*.store') && !$this->routeIs('api.v1.*') && $hasLocationKey) {
                Log::debug('Is POST + store route.');
                $hasLocation = $this->boolean($hasLocationKey);
                if (true === $hasLocation) {
                    Log::debug('Has form form location');

                    return true;
                }
                Log::debug('Does not have form location');

                return false;
            }
            Log::debug('Is not POST API or POST form');

            return false;
        }
        Log::debug('Fields not present');

        return false;
    }

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
     * @param string|null $prefix
     *
     * @return bool
     */
    private function isValidPUT(?string $prefix): bool
    {
        $longitudeKey   = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey    = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey   = $this->getLocationKey($prefix, 'zoom_level');
        $hasLocationKey = $this->getLocationKey($prefix, 'has_location');
        Log::debug('Now in isValidPUT()');

        // all fields must be set:
        if (null !== $this->get($longitudeKey) && null !== $this->get($latitudeKey) && null !== $this->get($zoomLevelKey)) {
            Log::debug('All fields present.');
            // must be PUT and API route:
            if ('PUT' === $this->method() && $this->routeIs('api.v1.*')) {
                Log::debug('Is API location');

                return true;
            }
            // if POST and not API route, must also have "has_location"
            // if is POST and route does not contain API, must also have "has_location" = true
            if ('POST' === $this->method() && $this->routeIs('*.update') && !$this->routeIs('api.v1.*') && $hasLocationKey) {
                Log::debug('Is POST + store route.');
                $hasLocation = $this->boolean($hasLocationKey);
                if (true === $hasLocation) {
                    Log::debug('Has form location data + has_location');

                    return true;
                }
                Log::debug('Does not have form location');

                return false;
            }
            Log::debug('Is not POST API or POST form');

            return false;
        }
        Log::debug('Fields not present');

        return false;
    }

    /**
     * @param string|null $prefix
     *
     * @return bool
     */
    private function isValidEmptyPUT(?string $prefix): bool
    {
        $longitudeKey = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey  = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey = $this->getLocationKey($prefix, 'zoom_level');

        return (
                   null === $this->get($longitudeKey)
                   && null === $this->get($latitudeKey)
                   && null === $this->get($zoomLevelKey))
               && ('PUT' === $this->method()
                   || ('POST' === $this->method() && $this->routeIs('*.update'))
               );

    }

    /**
     * Abstract method to ensure filling later.
     *
     * @param string $field
     *
     * @return string|null
     */
    abstract protected function nullableString(string $field): ?string;

}
