<?php

/**
 * ConfigurationRequest.php
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

namespace FireflyIII\Api\V1\Requests\System;

use Carbon\Carbon;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CronRequest
 */
class CronRequest extends FormRequest
{
    use ConvertsDataTypes;

    /**
     * Verify the request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $data = [
            'force' => false,
            'date'  => today(config('app.timezone')),
        ];
        if ($this->has('force')) {
            $data['force'] = $this->boolean('force');
        }
        if ($this->has('date')) {
            $data['date'] = $this->getCarbonDate('date');
        }
        // catch NULL.
        if (!$data['date'] instanceof Carbon) {
            $data['date'] = today(config('app.timezone'));
        }

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'force' => 'in:true,false',
            'date'  => 'nullable|date|after:1970-01-02|before:2038-01-17',
        ];
    }
}
