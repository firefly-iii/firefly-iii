<?php

/**
 * TagFormRequest.php
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

use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use FireflyIII\Support\Request\AppendsLocationData;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Class TagFormRequest.
 */
class TagFormRequest extends FormRequest
{
    use AppendsLocationData;
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data for controller.
     */
    public function collectTagData(): array
    {
        $data = [
            'tag'         => $this->convertString('tag'),
            'date'        => $this->getCarbonDate('date'),
            'description' => $this->convertString('description'),
        ];

        return $this->appendLocationData($data, 'location');
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $idRule  = '';

        /** @var null|Tag $tag */
        $tag     = $this->route()->parameter('tag');
        $tagRule = 'required|max:1024|min:1|uniqueObjectForUser:tags,tag';
        if (null !== $tag) {
            $idRule  = 'belongsToUser:tags';
            $tagRule = 'required|max:1024|min:1|uniqueObjectForUser:tags,tag,'.$tag->id;
        }

        $rules   = [
            'tag'         => $tagRule,
            'id'          => $idRule,
            'description' => 'max:32768|min:1|nullable',
            'date'        => 'date|nullable|after:1984-09-17',
        ];

        return Location::requestRules($rules);
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
