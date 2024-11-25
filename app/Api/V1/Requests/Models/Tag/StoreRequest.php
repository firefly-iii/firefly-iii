<?php

/**
 * TagStoreRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Tag;

use FireflyIII\Models\Location;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreRequest
 */
class StoreRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        $data = [
            'tag'          => $this->convertString('tag'),
            'date'         => $this->getCarbonDate('date'),
            'description'  => $this->convertString('description'),
            'has_location' => true,
        ];

        return $this->appendLocationData($data, null);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        $rules = [
            'tag'         => 'required|min:1|uniqueObjectForUser:tags,tag|max:1024',
            'description' => 'min:1|nullable|max:32768',
            'date'        => 'date|nullable',
        ];

        return Location::requestRules($rules);
    }
}
