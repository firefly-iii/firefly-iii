<?php
/**
 * SpecificInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Specifics;

/**
 * Interface SpecificInterface
 *
 * @package FireflyIII\Import\Specifics
 */
interface SpecificInterface
{
    /**
     * @return string
     */
    static public function getName(): string;

    /**
     * @return string
     */
    static public function getDescription(): string;

}