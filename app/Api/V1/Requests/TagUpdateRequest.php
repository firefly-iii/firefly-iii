<?php

/**
 * TagUpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests;

/**
 * Class TagUpdateRequest
 *
 * @codeCoverageIgnore
 *
 */
class TagUpdateRequest extends Request
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
        return [
            'tag'         => $this->string('tag'),
            'date'        => $this->date('date'),
            'description' => $this->string('description'),
            'latitude'    => '' === $this->string('latitude') ? null : $this->string('latitude'),
            'longitude'   => '' === $this->string('longitude') ? null : $this->string('longitude'),
            'zoom_level'  => $this->integer('zoom_level'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $tag = $this->route()->parameter('tagOrId');

        return [
            'tag'         => 'required|min:1|uniqueObjectForUser:tags,tag,' . $tag->id,
            'description' => 'min:1|nullable',
            'date'        => 'date|nullable',
            'latitude'    => 'numeric|min:-90|max:90|nullable|required_with:longitude',
            'longitude'   => 'numeric|min:-180|max:180|nullable|required_with:latitude',
            'zoom_level'  => 'numeric|min:0|max:80|nullable',
        ];
    }
}
