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
    public static function getDescription(): string;

    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array;

}
