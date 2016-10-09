<?php
/**
 * Bills.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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

        $list = [0 => trans('csv.do_not_map')] + $list;

        return $list;

    }
}
