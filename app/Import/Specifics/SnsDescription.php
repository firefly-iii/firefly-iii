<?php
/**
 * SnsDescription.php
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

/**
 * snsDescription.php
 * Author 2017 hugovanduijn@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Specifics;

use Log;

/**
 * Class SnsDescription
 *
 * @package FireflyIII\Import\Specifics
 */
class SnsDescription implements SpecificInterface
{
    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Trim quotes from SNS descriptions.';
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'SNS description';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        $row[17]  = ltrim($row[17],"'");
        $row[17]  = rtrim($row[17],"'");
        return $row;
    }
}
