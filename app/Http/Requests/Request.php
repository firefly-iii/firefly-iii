<?php
/**
 * Request.php
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

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use Exception;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Log;

/**
 * Class Request.
 *
 * @codeCoverageIgnore
 *
 */
class Request extends FormRequest
{
    use ConvertsDataTypes;


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

        return (float) $res;
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
        $value = (string) $this->get($field);
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
}
