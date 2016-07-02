<?php
/**
 * MapperInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Mapper;

/**
 * Interface MapperInterface
 *
 * @package FireflyIII\Import\Mapper
 */
interface MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array;
}
