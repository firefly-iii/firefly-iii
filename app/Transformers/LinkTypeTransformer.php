<?php

/**
 * LinkTypeTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\LinkType;

/**
 * Class LinkTypeTransformer
 */
class LinkTypeTransformer extends AbstractTransformer
{
    /**
     * Transform the currency.
     */
    public function transform(LinkType $linkType): array
    {
        return [
            'id'         => $linkType->id,
            'created_at' => $linkType->created_at->toAtomString(),
            'updated_at' => $linkType->updated_at->toAtomString(),
            'name'       => $linkType->name,
            'inward'     => $linkType->inward,
            'outward'    => $linkType->outward,
            'editable'   => $linkType->editable,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/link_types/'.$linkType->id,
                ],
            ],
        ];
    }
}
