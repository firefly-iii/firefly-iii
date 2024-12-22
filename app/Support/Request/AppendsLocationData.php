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

/**
 * Trait AppendsLocationData
 */
trait AppendsLocationData
{
    public function addFromromTransactionStore(array $information, array $return): array
    {
        $return['store_location'] = false;
        if (true === $information['store_location']) {
            $long = array_key_exists('longitude', $information) ? $information['longitude'] : null;
            $lat  = array_key_exists('latitude', $information) ? $information['latitude'] : null;
            if (null !== $long && null !== $lat && $this->validLongitude($long) && $this->validLatitude($lat)) {
                $return['store_location'] = true;
                $return['longitude']      = $information['longitude'];
                $return['latitude']       = $information['latitude'];
                $return['zoom_level']     = $information['zoom_level'];
            }
        }

        return $return;
    }

    private function validLongitude(string $longitude): bool
    {
        $number = (float) $longitude;

        return $number >= -180 && $number <= 180;
    }

    private function validLatitude(string $latitude): bool
    {
        $number = (float) $latitude;

        return $number >= -90 && $number <= 90;
    }

    /**
     * Abstract method.
     *
     * @param mixed $key
     *
     * @return bool
     */
    abstract public function has($key);

    /**
     * Read the submitted Request data and add new or updated Location data to the array.
     */
    protected function appendLocationData(array $data, ?string $prefix): array
    {
        app('log')->debug(sprintf('Now in appendLocationData("%s")', $prefix), $data);
        $data['store_location']  = false;
        $data['update_location'] = false;
        $data['remove_location'] = false;
        $data['longitude']       = null;
        $data['latitude']        = null;
        $data['zoom_level']      = null;

        $longitudeKey            = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey             = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey            = $this->getLocationKey($prefix, 'zoom_level');
        $isValidPOST             = $this->isValidPost($prefix);
        $isValidPUT              = $this->isValidPUT($prefix);
        $isValidEmptyPUT         = $this->isValidEmptyPUT($prefix);

        // for a POST (store), all fields must be present and not NULL.
        if ($isValidPOST) {
            app('log')->debug('Method is POST and all fields present and not NULL.');
            $data['store_location'] = true;
            $data['longitude']      = $this->convertString($longitudeKey);
            $data['latitude']       = $this->convertString($latitudeKey);
            $data['zoom_level']     = $this->convertString($zoomLevelKey);
        }

        // for a PUT (api update) or POST update (UI)
        if ($isValidPUT) {
            app('log')->debug('Method is PUT and all fields present and not NULL.');
            $data['update_location'] = true;
            $data['longitude']       = $this->convertString($longitudeKey);
            $data['latitude']        = $this->convertString($latitudeKey);
            $data['zoom_level']      = $this->convertString($zoomLevelKey);
        }
        if ($isValidEmptyPUT) {
            app('log')->debug('Method is PUT and all fields present and NULL.');
            $data['remove_location'] = true;
        }
        app('log')->debug(sprintf('Returning longitude: "%s", latitude: "%s", zoom level: "%s"', $data['longitude'], $data['latitude'], $data['zoom_level']));
        app('log')->debug(
            sprintf(
                'Returning actions: store: %s, update: %s, delete: %s',
                var_export($data['store_location'], true),
                var_export($data['update_location'], true),
                var_export($data['remove_location'], true),
            )
        );

        return $data;
    }

    private function getLocationKey(?string $prefix, string $key): string
    {
        if (null === $prefix) {
            return $key;
        }

        return sprintf('%s_%s', $prefix, $key);
    }

    private function isValidPost(?string $prefix): bool
    {
        app('log')->debug('Now in isValidPost()');
        $longitudeKey   = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey    = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey   = $this->getLocationKey($prefix, 'zoom_level');
        $hasLocationKey = $this->getLocationKey($prefix, 'has_location');
        // fields must not be null:
        if (null !== $this->get($longitudeKey) && null !== $this->get($latitudeKey) && null !== $this->get($zoomLevelKey)) {
            app('log')->debug('All fields present');
            // if is POST and route contains API, this is enough:
            if ('POST' === $this->method() && $this->routeIs('api.v1.*')) {
                app('log')->debug('Is API location');

                return true;
            }
            // if is POST and route does not contain API, must also have "has_location" = true
            if ('POST' === $this->method() && $this->routeIs('*.store') && !$this->routeIs('api.v1.*') && '' !== $hasLocationKey) {
                app('log')->debug('Is POST + store route.');
                $hasLocation = $this->boolean($hasLocationKey);
                if (true === $hasLocation) {
                    app('log')->debug('Has form form location');

                    return true;
                }
                app('log')->debug('Does not have form location');

                return false;
            }
            app('log')->debug('Is not POST API or POST form');

            return false;
        }
        app('log')->debug('Fields not present');

        return false;
    }

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
     * Abstract method stolen from "InteractsWithInput".
     *
     * @param null $key
     * @param bool $default
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    abstract public function boolean($key = null, $default = false);

    private function isValidPUT(?string $prefix): bool
    {
        $longitudeKey   = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey    = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey   = $this->getLocationKey($prefix, 'zoom_level');
        $hasLocationKey = $this->getLocationKey($prefix, 'has_location');
        app('log')->debug('Now in isValidPUT()');

        // all fields must be set:
        if (null !== $this->get($longitudeKey) && null !== $this->get($latitudeKey) && null !== $this->get($zoomLevelKey)) {
            app('log')->debug('All fields present.');
            // must be PUT and API route:
            if ('PUT' === $this->method() && $this->routeIs('api.v1.*')) {
                app('log')->debug('Is API location');

                return true;
            }
            // if POST and not API route, must also have "has_location"
            // if is POST and route does not contain API, must also have "has_location" = true
            if ('POST' === $this->method() && $this->routeIs('*.update') && !$this->routeIs('api.v1.*') && '' !== $hasLocationKey) {
                app('log')->debug('Is POST + store route.');
                $hasLocation = $this->boolean($hasLocationKey);
                if (true === $hasLocation) {
                    app('log')->debug('Has form location data + has_location');

                    return true;
                }
                app('log')->debug('Does not have form location');

                return false;
            }
            app('log')->debug('Is not POST API or POST form');

            return false;
        }
        app('log')->debug('Fields not present');

        return false;
    }

    private function isValidEmptyPUT(?string $prefix): bool
    {
        $longitudeKey = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey  = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey = $this->getLocationKey($prefix, 'zoom_level');

        return (
            null === $this->get($longitudeKey)
            && null === $this->get($latitudeKey)
            && null === $this->get($zoomLevelKey))
               && (
                   'PUT' === $this->method()
                   || ('POST' === $this->method() && $this->routeIs('*.update'))
               );
    }
}
