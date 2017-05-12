<?php
/**
 * ChartColour.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
