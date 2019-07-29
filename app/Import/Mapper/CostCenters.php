<?php
/**
 * CostCenters.php
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

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\CostCenter;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;

/**
 * Class CostCenters.
 */
class CostCenters implements MapperInterface
{
    /**
     * Get map of cost centers.
     *
     * @return array
     */
    public function getMap(): array
    {
        /** @var CostCenterRepositoryInterface $repository */
        $repository = app(CostCenterRepositoryInterface::class);
        $result     = $repository->getCostCenters();
        $list       = [];
        
        /** @var CostCenter $costCenter */
        foreach ($result as $costCenter) {
            $costCenterId        = (int)$costCenter->id;
            $list[$costCenterId] = $costCenter->name;
        }
        asort($list);
        $list = [0 => (string)trans('import.map_do_not_map')] + $list;

        return $list;
    }
}
