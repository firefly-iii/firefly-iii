<?php

/**
 * TagUpdateRequest.php
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
use FireflyIII\Models\Tag;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        // This is the way.
        $fields = [
            'tag'         => ['tag', 'convertString'],
            'date'        => ['date', 'date'],
            'description' => ['description', 'convertString'],
        ];
        $data   = $this->getAllData($fields);

        return $this->appendLocationData($data, null);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        /** @var Tag $tag */
        $tag   = $this->route()->parameter('tagOrId');
        $rules = [
            'tag'         => 'min:1|max:1024|uniqueObjectForUser:tags,tag,'.$tag->id,
            'description' => 'min:1|nullable|max:32768',
            'date'        => 'date|nullable|after:1970-01-02|before:2038-01-17',
        ];

        return Location::requestRules($rules);
    }
}
