<?php
/**
 * PiggyBankChartGeneratorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
