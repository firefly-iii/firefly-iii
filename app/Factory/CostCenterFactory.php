<?php
/**
 * CostCenterFactory.php
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
/** @noinspection MultipleReturnStatementsInspection */
declare(strict_types=1);

namespace FireflyIII\Factory;


use FireflyIII\Models\CostCenter;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CostCenterFactory
 */
class CostCenterFactory
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param string $name
     *
     * @return CostCenter|null
     */
    public function findByName(string $name): ?CostCenter
    {
        $result = null;
        /** @var Collection $collection */
        $collection = $this->user->costCenters()->get();
        /** @var CostCenter $costCenter */
        foreach ($collection as $costCenter) {
            if ($costCenter->name === $name) {
                $result = $costCenter;
                break;
            }
        }

        return $result;
    }

    /**
     * @param int|null    $costCenterId
     * @param null|string $costCenterName
     *
     * @return CostCenter|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function findOrCreate(?int $costCenterId, ?string $costCenterName): ?CostCenter
    {
        $costCenterId   = (int)$costCenterId;
        $costCenterName = (string)$costCenterName;

        Log::debug(sprintf('Going to find cost center with ID %d and name "%s"', $costCenterId, $costCenterName));

        if ('' === $costCenterName && 0 === $costCenterId) {
            return null;
        }
        // first by ID:
        if ($costCenterId > 0) {
            /** @var CostCenter $costCenter */
            $costCenter = $this->user->costCenters()->find($costCenterId);
            if (null !== $costCenter) {
                return $costCenter;
            }
        }

        if ('' !== $costCenterName) {
            $costCenter = $this->findByName($costCenterName);
            if (null !== $costCenter) {
                return $costCenter;
            }

            return CostCenter::create(
                [
                    'user_id' => $this->user->id,
                    'name'    => $costCenterName,
                ]
            );
        }

        return null;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
