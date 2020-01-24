<?php
/**
 * Request.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Log;

/**
 * Class Request.
 *
 * @codeCoverageIgnore
 *
 */
class Request extends FormRequest
{
    /**
     * @param $array
     *
     * @return array|null
     */
    public function arrayFromValue($array): ?array
    {
        if (is_array($array)) {
            return $array;
        }
        if (null === $array) {
            return null;
        }
        if (is_string($array)) {
            return explode(',', $array);
        }

        return null;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function convertBoolean(?string $value): bool
    {
        if (null === $value) {
            return false;
        }
        if ('true' === $value) {
            return true;
        }
        if ('yes' === $value) {
            return true;
        }
        if (1 === $value) {
            return true;
        }
        if ('1' === $value) {
            return true;
        }
        if (true === $value) {
            return true;
        }

        return false;
    }

    /**
     * @param string|null $string
     *
     * @return Carbon|null
     */
    public function dateFromValue(?string $string): ?Carbon
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }
        try {
            $carbon = new Carbon($string);
        } catch (Exception $e) {
            Log::debug(sprintf('Invalid date: %s: %s', $string, $e->getMessage()));

            return null;
        }

        return $carbon;
    }

    /**
     * Return floating value.
     *
     * @param string $field
     *
     * @return float|null
     */
    public function float(string $field): ?float
    {
        $res = $this->get($field);
        if (null === $res) {
            return null;
        }

        return (float)$res;
    }

    /**
     * Return integer value.
     *
     * @param string $field
     *
     * @return int
     */
    public function integer(string $field): int
    {
        return (int)$this->get($field);
    }

    /**
     * Parse to integer
     *
     * @param string|null $string
     *
     * @return int|null
     */
    public function integerFromValue(?string $string): ?int
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }

        return (int)$string;
    }

    /**
     * Return integer value, or NULL when it's not set.
     *
     * @param string $field
     *
     * @return int|null
     */
    public function nullableInteger(string $field): ?int
    {
        if (!$this->has($field)) {
            return null;
        }

        $value = (string)$this->get($field);
        if ('' === $value) {
            return null;
        }

        return (int)$value;
    }

    /**
     * Return string value, or NULL if empty.
     *
     * @param string $field
     *
     * @return string|null
     */
    public function nullableString(string $field): ?string
    {
        if (!$this->has($field)) {
            return null;
        }
        return app('steam')->cleanString((string)($this->get($field) ?? ''));
    }

    /**
     * Return string value.
     *
     * @param string $field
     *
     * @return string
     */
    public function string(string $field): string
    {
        return app('steam')->cleanString((string)($this->get($field) ?? ''));
    }

    /**
     * Return string value, but keep newlines.
     *
     * @param string $field
     *
     * @return string
     */
    public function nlString(string $field): string
    {
        return app('steam')->nlCleanString((string)($this->get($field) ?? ''));
    }


    /**
     * Return string value, but keep newlines, or NULL if empty.
     *
     * @param string $field
     *
     * @return string
     */
    public function nullableNlString(string $field): ?string
    {
        if (!$this->has($field)) {
            return null;
        }
        return app('steam')->nlCleanString((string)($this->get($field) ?? ''));
    }

    /**
     * Parse and clean a string.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public function stringFromValue(?string $string): ?string
    {
        if (null === $string) {
            return null;
        }
        $result = app('steam')->cleanString($string);

        return '' === $result ? null : $result;

    }

    /**
     * Parse and clean a string, but keep the newlines.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public function nlStringFromValue(?string $string): ?string
    {
        if (null === $string) {
            return null;
        }
        $result = app('steam')->nlCleanString($string);

        return '' === $result ? null : $result;

    }

    /**
     * Return date or NULL.
     *
     * @param string $field
     *
     * @return Carbon|null
     */
    protected function date(string $field): ?Carbon
    {
        $result = null;
        try {
            $result = $this->get($field) ? new Carbon($this->get($field)) : null;
        } catch (Exception $e) {
            Log::debug(sprintf('Exception when parsing date. Not interesting: %s', $e->getMessage()));
        }

        return $result;
    }

    /**
     * Return date time or NULL.
     *
     * @param string $field
     *
     * @return Carbon|null
     */
    protected function dateTime(string $field): ?Carbon
    {
        if (null === $this->get($field)) {
            return null;
        }
        $value = (string)$this->get($field);
        if (10 === strlen($value)) {
            // probably a date format.
            try {
                $result = Carbon::createFromFormat('Y-m-d', $value);
            } catch (InvalidDateException $e) {
                Log::error(sprintf('"%s" is not a valid date: %s', $value, $e->getMessage()));

                return null;
            }

            return $result;
        }
        // is an atom string, I hope?
        try {
            $result = Carbon::parse($value);
        } catch (InvalidDateException $e) {
            Log::error(sprintf('"%s" is not a valid date or time: %s', $value, $e->getMessage()));

            return null;
        }

        return $result;
    }

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


        $longitudeKey = null === $prefix ? 'longitude' : sprintf('%s_longitude', $prefix);
        $latitudeKey  = null === $prefix ? 'latitude' : sprintf('%s_latitude', $prefix);
        $zoomLevelKey = null === $prefix ? 'zoom_level' : sprintf('%s_zoom_level', $prefix);

        // for a POST (store, all fields must be present and accounted for:
        if (
            ('POST' === $this->method() && $this->routeIs('*.store'))
            && ($this->has($longitudeKey) && $this->has($latitudeKey) && $this->has($zoomLevelKey))
        ) {
            Log::debug('Method is POST and all fields present.');
            $data['store_location'] = true;
            $data['longitude']      = '' === $this->string($longitudeKey) ? null : $this->string($longitudeKey);
            $data['latitude']       = '' === $this->string($latitudeKey) ? null : $this->string($latitudeKey);
            $data['zoom_level']     = '' === $this->string($zoomLevelKey) ? null : $this->integer($zoomLevelKey);
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
            $data['longitude']       = '' === $this->string($longitudeKey) ? null : $this->string($longitudeKey);
            $data['latitude']        = '' === $this->string($latitudeKey) ? null : $this->string($latitudeKey);
            $data['zoom_level']      = '' === $this->string($zoomLevelKey) ? null : $this->integer($zoomLevelKey);
        }
        if (null === $data['longitude'] || null === $data['latitude'] || null === $data['zoom_level']) {
            Log::debug('One of the fields is NULL, wont save.');
            $data['store_location']  = false;
            $data['update_location'] = false;
        }

        Log::debug(sprintf('Returning longitude: "%s", latitude: "%s", zoom level: "%s"', $data['longitude'], $data['latitude'], $data['zoom_level']));

        return $data;
    }

}
