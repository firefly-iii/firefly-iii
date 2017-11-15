<?php
/**
 * TagFormRequest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;

/**
 * Class TagFormRequest.
 */
class TagFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function collectTagData(): array
    {
        $latitude  = null;
        $longitude = null;
        $zoomLevel = null;

        if ('true' === $this->get('tag_position_has_tag')) {
            $latitude  = $this->string('tag_position_latitude');
            $longitude = $this->string('tag_position_longitude');
            $zoomLevel = $this->integer('tag_position_zoomlevel');
        }

        $data = [
            'tag'         => $this->string('tag'),
            'date'        => $this->date('date'),
            'description' => $this->string('description'),
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $idRule     = '';
        $tagRule    = 'required|min:1|uniqueObjectForUser:tags,tag';
        if (null !== $repository->find(intval($this->get('id')))->id) {
            $idRule  = 'belongsToUser:tags';
            $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag,' . $this->get('id');
        }

        return [
            'tag'         => $tagRule,
            'id'          => $idRule,
            'description' => 'min:1|nullable',
            'date'        => 'date|nullable',
            'latitude'    => 'numeric|min:-90|max:90|nullable',
            'longitude'   => 'numeric|min:-90|max:90|nullable',
            'zoomLevel'   => 'numeric|min:0|max:80|nullable',
        ];
    }
}
