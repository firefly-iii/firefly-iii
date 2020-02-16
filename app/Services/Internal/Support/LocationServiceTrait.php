<?php
/**
 * LocationServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use FireflyIII\Models\Location;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LocationServiceTrait
 */
trait LocationServiceTrait
{
    /**
     * @param Model $model
     * @param array $data
     *
     * @return Location|null
     */
    protected function storeNewLocation(Model $model, array $data): ?Location
    {
        $data['store_location'] = $data['store_location'] ?? false;
        if ($data['store_location']) {
            $location             = new Location;
            $location->latitude   = $data['latitude'] ?? config('firefly.default_location.latitude');
            $location->longitude  = $data['longitude'] ?? config('firefly.default_location.longitude');
            $location->zoom_level = $data['zoom_level'] ?? config('firefly.default_location.zoom_level');
            $location->locatable()->associate($model);
            $location->save();

            return $location;
        }

        return null;
    }

}