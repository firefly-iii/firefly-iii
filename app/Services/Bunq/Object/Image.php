<?php
/**
 * Image.php
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

namespace FireflyIII\Services\Bunq\Object;

/**
 * Class Image
 */
class Image extends BunqObject
{
    /** @var string */
    private $attachmentPublicUuid;
    /** @var string */
    private $contentType;
    /** @var int */
    private $height;
    /** @var int */
    private $width;

    /**
     * Image constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->attachmentPublicUuid = $data['attachment_public_uuid'] ?? null;
        $this->height               = $data['height'] ?? null;
        $this->width                = $data['width'] ?? null;
        $this->contentType          = $data['content_type'] ?? null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'attachment_public_uuid' => $this->attachmentPublicUuid,
            'height'                 => $this->height,
            'width'                  => $this->width,
            'content_type'           => $this->contentType,
        ];
    }

}
