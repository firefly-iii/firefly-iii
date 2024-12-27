<?php

/**
 * ObjectGroupTransformer.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;

/**
 * Class AccountTransformer
 */
class ObjectGroupTransformer extends AbstractTransformer
{
    protected ObjectGroupRepositoryInterface $repository;

    /**
     * AccountTransformer constructor.
     */
    public function __construct()
    {
        $this->repository = app(ObjectGroupRepositoryInterface::class);
    }

    /**
     * Transform the account.
     */
    public function transform(ObjectGroup $objectGroup): array
    {
        $this->repository->setUser($objectGroup->user);

        return [
            'id'         => (string) $objectGroup->id,
            'created_at' => $objectGroup->created_at?->toAtomString(),
            'updated_at' => $objectGroup->updated_at?->toAtomString(),
            'title'      => $objectGroup->title,
            'order'      => $objectGroup->order,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/object_groups/'.$objectGroup->id,
                ],
            ],
        ];
    }
}
