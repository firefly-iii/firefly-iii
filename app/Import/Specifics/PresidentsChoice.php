<?php
/**
 * PresidentsChoice.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Specifics;

/**
 * Class PresidentsChoice
 *
 * @package FireflyIII\Import\Specifics
 */
class PresidentsChoice implements SpecificInterface
{

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Fixes problems with files from Presidents Choice Financial.';
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'Presidents "Choice"';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        // first, if column 2 is empty and 3 is not, do nothing.
        // if column 3 is empty and column 2 is not, move amount to column 3, *-1
        if (isset($row[3]) && strlen($row[3]) === 0) {
            $row[3] = bcmul($row[2], '-1');
        }
        if (isset($row[1])) {
            // copy description into column 2, which is now usable.
            $row[2] = $row[1];
        }

        return $row;


    }
}
