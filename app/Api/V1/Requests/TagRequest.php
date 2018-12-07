<?php

/**
 * TagRequest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Models\Tag;
use Illuminate\Validation\Validator;

/**
 * Class TagRequest
 */
class TagRequest extends Request
{

    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $data = [
            'tag'         => $this->string('tag'),
            'date'        => $this->date('date'),
            'description' => $this->string('description'),
            'latitude'    => '' === $this->string('latitude') ? null : $this->string('latitude'),
            'longitude'   => '' === $this->string('longitude') ? null : $this->string('longitude'),
            'zoom_level'   => $this->integer('zoom_level'),
        ];

        return $data;
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'tag'         => 'required|min:1|uniqueObjectForUser:tags,tag',
            'description' => 'min:1|nullable',
            'date'        => 'date|nullable',
            'latitude'    => 'numeric|min:-90|max:90|nullable|required_with:longitude',
            'longitude'   => 'numeric|min:-90|max:90|nullable|required_with:latitude',
            'zoomLevel'   => 'numeric|min:0|max:80|nullable',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var Tag $tag */
                $tag          = $this->route()->parameter('tagOrId');
                $rules['tag'] = 'required|min:1|uniqueObjectForUser:tags,tag,' . $tag->id;
                break;
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                $data = $validator->getData();
                $min  = (float)($data['amount_min'] ?? 0);
                $max  = (float)($data['amount_max'] ?? 0);
                if ($min > $max) {
                    $validator->errors()->add('amount_min', (string)trans('validation.amount_min_over_max'));
                }
            }
        );
    }
}
