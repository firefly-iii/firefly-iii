<?php
/**
 * IngBelgium.php
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

namespace FireflyIII\Import\Specifics;

/**
 * Class IngBelgium.
 *
 * Parses the description and opposing account information (IBAN and name) from CSV files for ING Belgium bank accounts.
 *
 */
class IngBelgium implements SpecificInterface
{
    /**
     * Description of the current specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getDescription(): string
    {
        return 'import.specific_ingbelgium_descr';
    }

    /**
     * Name of the current specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getName(): string
    {
        return 'import.specific_ingbelgium_name';
    }

    /**
     * Run the specific code.
     *
     * @param array $row
     *
     * @return array
     *
     */
    public function run(array $row): array
    {
        return IngBelgium::processTransactionDetails($row);
    }

    /**
     * Gets the description and opposing account information (IBAN and name) from the transaction details and adds
     * them to the row of data.
     *
     * @return array the row containing the description and opposing account's IBAN
     */
    protected static function processTransactionDetails(array $row): array
    {
        if(isset($row[9])) {
            $transactionDetails = $row[9];
            $row[11] = IngBelgium::opposingAccountName($transactionDetails);
            $row[12] = IngBelgium::opposingAccountIban($transactionDetails);
            $row[13] = IngBelgium::description($transactionDetails);
        }
        return $row;
    }

    /**
     * Gets the opposing account name from the transaction details.
     *
     * @return string the opposing account name
     */
    protected static function opposingAccountName(string $transactionDetails): string
    {
        return IngBelgium::parseInformationFromTransactionDetails($transactionDetails, '/Van:\s*(.+?)(?=\s{2,})/');

    }

    /**
     * Gets the opposing account's IBAN from the transaction details.
     *
     * @return string the opposing account's IBAN
     */
    protected static function opposingAccountIban(string $transactionDetails): string
    {
        return IngBelgium::parseInformationFromTransactionDetails($transactionDetails, '/IBAN:\s*(.+?)(?=\s+)/');
    }

    /**
     * Gets the description from the transaction details and makes sure structured descriptions are in the
     * "+++090/9337/55493+++" format.
     *
     * @return string the description
     */
    protected static function description(string $transactionDetails): string
    {
        $description = IngBelgium::parseInformationFromTransactionDetails($transactionDetails, '/Mededeling:\s*(.+)$/');
        return IngBelgium::convertStructuredDescriptionToProperFormat($description);
    }

    private static function convertStructuredDescriptionToProperFormat(string $description): string
    {
        preg_match('/^\*\*\*(\d{3}\/\d{4}\/\d{5})\*\*\*$/', $description, $matches);
        if(isset($matches[1])) {
            return '+++' . $matches[1] . '+++';
        }
        return $description;
    }

    private static function parseInformationFromTransactionDetails(string $transactionDetails, string $regex): string
    {
        if(isset($transactionDetails)) {
            preg_match($regex, $transactionDetails, $matches);
            if (isset($matches[1])) {
                return trim($matches[1]);
            }
        }

        return '';
    }
}
