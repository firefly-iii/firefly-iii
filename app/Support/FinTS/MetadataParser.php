<?php
/**
 * MetadataParser.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\FinTS;

use Fhp\Model\StatementOfAccount\Transaction as FinTSTransaction;

/**
 *
 * Class MetadataParser
 */
class MetadataParser
{
    /**
     * @param FinTSTransaction $transaction
     *
     * @return string
     */
    public function getDescription(FinTSTransaction $transaction): string
    {
        //Given a description like 'EREF+AbcCRED+DE123SVWZ+DefABWA+Ghi' or 'EREF+AbcCRED+DE123SVWZ+Def' return 'Def'
        $finTSDescription = $transaction->getDescription1();
        $matches          = [];
        if (1 === preg_match('/SVWZ\+([^\+]*)([A-Z]{4}\+|$)/', $finTSDescription, $matches)) {
            return $matches[1];
        }

        return $finTSDescription;
    }
}
