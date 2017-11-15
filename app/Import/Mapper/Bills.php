<?php
/**
 * Bills.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;

/**
 * Class Bills
 *
 * @package FireflyIII\Import\Mapper
 */
class Bills implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $result     = $repository->getBills();
        $list       = [];

        /** @var Bill $bill */
        foreach ($result as $bill) {
            $list[$bill->id] = $bill->name . ' [' . $bill->match . ']';
        }
        asort($list);

        $list = [0 => trans('csv.map_do_not_map')] + $list;

        return $list;
    }
}
