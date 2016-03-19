<?php
declare(strict_types = 1);
/**
 * BillChartGeneratorInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Generator\Chart\Bill;


use FireflyIII\Models\Bill;
use Illuminate\Support\Collection;

/**
 * Interface BillChartGeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\Bill
 */
interface BillChartGeneratorInterface
{

    /**
     * @param string $paid
     * @param string $unpaid
     *
     * @return array
     */
    public function frontpage(string $paid, string $unpaid): array;

    /**
     * @param Bill       $bill
     * @param Collection $entries
     *
     * @return array
     */
    public function single(Bill $bill, Collection $entries): array;

}
