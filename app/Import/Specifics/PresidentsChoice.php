<?php
/**
 * PresidentsChoice.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
