<?php
/**
 * PreProcessorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\MapperPreProcess;

/**
 * Interface PreProcessorInterface
 *
 * @package FireflyIII\Import\MapperPreProcess
 */
interface PreProcessorInterface
{
    /**
     * @param string $value
     *
     * @return array
     */
    public function run(string $value): array;

}
