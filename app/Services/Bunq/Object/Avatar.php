<?php
/**
 * Avatar.php
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

namespace FireflyIII\Services\Bunq\Object;

/**
 * @deprecated
 * @codeCoverageIgnore
 * Class Avatar.
 */
class Avatar extends BunqObject
{
    /** @var string */
    private $anchorUuid;
    /** @var Image */
    private $image;
    /** @var string */
    private $uuid;

    /**
     * Avatar constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->uuid       = $data['uuid'];
        $this->anchorUuid = $data['anchor_uuid'];
        $this->image      = new Image($data['image']);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid'        => $this->uuid,
            'anchor_uuid' => $this->anchorUuid,
            'image'       => $this->image->toArray(),
        ];
    }
}
