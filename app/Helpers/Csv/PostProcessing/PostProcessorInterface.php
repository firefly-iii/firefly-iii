<?php
/**
 * PostProcessorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\PostProcessing;


/**
 * Interface PostProcessorInterface
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
interface PostProcessorInterface
{

    /**
     * @return array
     */
    public function process(): array;

    /**
     * @param array $data
     */
    public function setData(array $data);
}
