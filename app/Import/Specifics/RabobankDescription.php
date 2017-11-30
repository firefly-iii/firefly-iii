<?php
/**
 * RabobankDescription.php
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

use Log;

/**
 * Class RabobankDescription.
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
                    ' both empty. Will use "%s" (from description) instead',
                    $alternateName
                )
            );
            $row[6]  = $alternateName;
            $row[10] = '';
        }
        if (!(strlen($oppositeAccount) < 1 && strlen($oppositeName) < 1)) {
            Log::debug('Rabobank specific: either opposite account or name are filled.');
        }

        return $row;
    }
}
