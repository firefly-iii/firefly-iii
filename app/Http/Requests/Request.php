<?php
/**
 * Request.php
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

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Request.
 * @codeCoverageIgnore
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Request extends FormRequest
{
    /**
     * Return a boolean value.
     *
     * @param string $field
     *
     * @return bool
     */
    public function boolean(string $field): bool
    {
        if ('true' === (string)$this->input($field)) {
            return true;
        }
        if ('false' === (string)$this->input($field)) {
            return false;
        }

        return 1 === (int)$this->input($field);
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
     * Return string value.
     *
     * @param string $field
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function string(string $field): string
    {
        return app('steam')->cleanString((string)($this->get($field) ?? ''));
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
        return $this->get($field) ? new Carbon($this->get($field)) : null;
    }
}
