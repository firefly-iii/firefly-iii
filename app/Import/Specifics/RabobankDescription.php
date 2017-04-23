<?php
/**
 * RabobankDescription.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
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
 * Class RabobankDescription
 *
 * @package FireflyIII\Import\Specifics
 */
class RabobankDescription implements SpecificInterface
{
    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Fixes possible problems with Rabobank descriptions.';
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'Rabobank description';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        Log::debug(sprintf('Now in RabobankSpecific::run(). Row has %d columns', count($row)));
        $oppositeAccount = isset($row[5]) ? trim($row[5]) : '';
        $oppositeName    = isset($row[6]) ? trim($row[6]) : '';
        $alternateName   = isset($row[10]) ? trim($row[10]) : '';

        if (strlen($oppositeAccount) < 1 && strlen($oppositeName) < 1) {
            Log::debug(
                sprintf(
                    'Rabobank specific: Opposite account and opposite name are' .
                    ' both empty. Will use "%s" (from description) instead', $alternateName
                )
            );
            $row[6]  = $alternateName;
            $row[10] = '';
        } else {
            Log::debug('Rabobank specific: either opposite account or name are filled.');
        }

        return $row;
    }
}
