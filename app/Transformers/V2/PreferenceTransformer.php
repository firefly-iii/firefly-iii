<?php

/**
 * PreferenceTransformer.php
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

namespace FireflyIII\Transformers\V2;

use FireflyIII\Models\Preference;
use Illuminate\Support\Collection;

/**
 * Class PreferenceTransformer
 *
 * @deprecated
 */
class PreferenceTransformer extends AbstractTransformer
{
    public function collectMetaData(Collection $objects): Collection
    {
        // TODO: Implement collectMetaData() method.
        return $objects;
    }

    /**
     * Transform the preference
     */
    public function transform(Preference $preference): array
    {
        return [
            'id'         => $preference->id,
            'created_at' => $preference->created_at->toAtomString(),
            'updated_at' => $preference->updated_at->toAtomString(),
            'name'       => $preference->name,
            'data'       => $preference->data,
        ];
    }
}
