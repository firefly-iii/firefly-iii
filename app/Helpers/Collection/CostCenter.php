<?php
/**
 * Category.php
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

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\CostCenter as CostCenterModel;
use Illuminate\Support\Collection;

/**
 * Class CostCenter.
 *
 * @codeCoverageIgnore
 */
class CostCenter
{
    /** @var Collection The costCenters */
    protected $costCenters;
    /** @var string Total amount */
    protected $total = '0';

    /**
     * CostCenter constructor.
     */
    public function __construct()
    {
        $this->costCenters = new Collection;
    }

    /**
     * Add a category.
     *
     * @param CostCenterModel $costCenter
     */
    public function addCostCenter(CostCenterModel $costCenter): void
    {
        // spent is minus zero for an expense report:
        if ($costCenter->spent < 0) {
            $this->costCenters->push($costCenter);
            $this->addTotal((string)$costCenter->spent);
        }
    }

    /**
     * Add to the total amount.
     *
     * @param string $add
     */
    public function addTotal(string $add): void
    {
        $this->total = bcadd($this->total, $add);
    }

    /**
     * Get all costCenters.
     *
     * @return Collection
     */
    public function getCostCenters(): Collection
    {
        $set = $this->costCenters->sortBy(
            function (CostCenterModel $costCenter) {
                return $costCenter->spent;
            }
        );

        return $set;
    }

    /**
     * Get the total.
     *
     * @return string
     */
    public function getTotal(): string
    {
        return $this->total;
    }
}
