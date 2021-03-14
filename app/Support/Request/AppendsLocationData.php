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

    /**
     * @param string|null $prefix
     *
     * @return bool
     */
    private function isValidPOST(?string $prefix): bool
    {
        $longitudeKey = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey  = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey = $this->getLocationKey($prefix, 'zoom_level');

        return ('POST' === $this->method() && $this->routeIs('*.store'))
               && (
                   null !== $this->get($longitudeKey)
                   && null !== $this->get($latitudeKey)
                   && null !== $this->get($zoomLevelKey)
               );
    }

    /**
     * @param string|null $prefix
     *
     * @return bool
     */
    private function isValidPUT(?string $prefix): bool
    {
        $longitudeKey = $this->getLocationKey($prefix, 'longitude');
        $latitudeKey  = $this->getLocationKey($prefix, 'latitude');
        $zoomLevelKey = $this->getLocationKey($prefix, 'zoom_level');

        return (
                   null !== $this->get($longitudeKey)
                   && null !== $this->get($latitudeKey)
                   && null !== $this->get($zoomLevelKey))
               && (('PUT' === $this->method() && $this->routeIs('*.update'))
                   || ('POST' === $this->method() && $this->routeIs('*.update')));
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

}
