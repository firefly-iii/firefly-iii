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

namespace FireflyIII\Transformers;

use FireflyIII\Models\Preference;

/**
 * Class PreferenceTransformer
 */
class PreferenceTransformer extends AbstractTransformer
{
    /**
     * Transform the preference
     */
    public function transform(Preference $preference): array
    {
        $userGroupId = 0 === $preference->user_group_id ? null : $preference->user_group_id;

        return [
            'id'            => $preference->id,
            'created_at'    => $preference->created_at->toAtomString(),
            'updated_at'    => $preference->updated_at->toAtomString(),
            'user_group_id' => $userGroupId,
            'name'          => $preference->name,
            'data'          => $preference->data,
        ];
    }
}
