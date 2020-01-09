<?php
/**
 * TagFormRequest.php
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

use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;

/**
 * Class TagFormRequest.
 */
class TagFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Get all data for controller.
     *
     * @return array
     */
    public function collectTagData(): array
    {
        $data = [
            'tag'         => $this->string('tag'),
            'date'        => $this->date('date'),
            'description' => $this->string('description'),
        ];
        return $this->appendLocationData($data, 'location');

    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $idRule = '';

        /** @var Tag $tag */
        $tag     = $this->route()->parameter('tag');
        $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag';
        if (null !== $tag) {
            $idRule  = 'belongsToUser:tags';
            $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag,' . $tag->id;
        }

        $rules= [
            'tag'         => $tagRule,
            'id'          => $idRule,
            'description' => 'min:1|nullable',
            'date'        => 'date|nullable',
        ];
        return Location::requestRules($rules);
    }
}
