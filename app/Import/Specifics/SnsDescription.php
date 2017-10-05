<?php
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
