<?php
declare(strict_types = 1);
/**
 * PiggyBankChartGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Generator\Chart\PiggyBank;

use Illuminate\Support\Collection;

/**
 * Interface PiggyBankChartGeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\PiggyBank
 */
interface PiggyBankChartGeneratorInterface
{
    /**
     * @param Collection $set
     *
     * @return array
     */
    public function history(Collection $set): array;
}
