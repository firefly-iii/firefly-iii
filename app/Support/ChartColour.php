<?php
/**
 * ChartColour.php
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

namespace FireflyIII\Support;

/**
 * Class ChartColour
 *
 * @package FireflyIII\Support
 */
class ChartColour
{
    public static $colours
        = [
            [53, 124, 165],
            [0, 141, 76],
            [219, 139, 11],
            [202, 25, 90],
            [85, 82, 153],
            [66, 133, 244],
            [219, 68, 55],
            [244, 180, 0],
            [15, 157, 88],
            [171, 71, 188],
            [0, 172, 193],
            [255, 112, 67],
            [158, 157, 36],
            [92, 107, 192],
            [240, 98, 146],
            [0, 121, 107],
            [194, 24, 91],
        ];

    /**
     * @param int $index
     *
     * @return string
     */
    public static function getColour(int $index): string
    {
        $index = $index % count(self::$colours);
        $row   = self::$colours[$index];

        return sprintf('rgba(%d, %d, %d, 0.7)', $row[0], $row[1], $row[2]);
    }
}
