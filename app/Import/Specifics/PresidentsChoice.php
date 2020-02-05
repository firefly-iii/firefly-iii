<?php
/**
 * PresidentsChoice.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Specifics;

/**
 * Class PresidentsChoice.
 */
class PresidentsChoice implements SpecificInterface
{
    /**
     * Description of specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getDescription(): string
    {
        return 'import.specific_pres_descr';
    }

    /**
     * Name of specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getName(): string
    {
        return 'import.specific_pres_name';
    }

    /**
     * Run this specific.
     *
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        $row = array_values($row);
        // first, if column 2 is empty and 3 is not, do nothing.
        // if column 3 is empty and column 2 is not, move amount to column 3, *-1
        if (isset($row[3]) && '' === $row[3]) {
            $row[3] = bcmul($row[2], '-1');
        }
        if (isset($row[1])) {
            // copy description into column 2, which is now usable.
            $row[2] = $row[1];
        }

        return $row;
    }
}
