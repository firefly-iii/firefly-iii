<?php
/**
 * IngDescription.php
 * Copyright (c) 2019 https://github.com/tomwerf
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
 * Class IngDescription.
 *
 * Parses the description from CSV files for Ing bank accounts.
 *
 * With Mutation 'InternetBankieren', 'Overschrijving', 'Verzamelbetaling' and
 * 'Incasso' the Name of Opposing account the Opposing IBAN number are in the
 * Description. This class will remove them, and add Name in description by
 * 'Betaalautomaat' so those are easily recognizable
 */
class IngDescription implements SpecificInterface
{
    /** @var array The current row. */
    public $row;

    /**
     * Description of the current specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getDescription(): string
    {
        return 'import.specific_ing_descr';
    }

    /**
     * Name of the current specific.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getName(): string
    {
        return 'import.specific_ing_name';
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
        $this->row = array_values($row);
        if (count($this->row) >= 8) {                    // check if the array is correct
            switch ($this->row[4]) {                     // Get value for the mutation type
                case 'GT':                               // InternetBankieren
                case 'OV':                               // Overschrijving
                case 'VZ':                               // Verzamelbetaling
                case 'IC':                               // Incasso
                    $this->removeIBANIngDescription();
                    $this->removeNameIngDescription();
                    // if "tegenrekening" empty, copy the description. Primitive, but it works.
                    $this->copyDescriptionToOpposite();
                    break;
                case 'BA':                              // Betaalautomaat
                    $this->addNameIngDescription();
                    break;
            }
        }

        return $this->row;
    }

    /**
     * Add the Opposing name from cell 1 in the description for Betaalautomaten
     * Otherwise the description is only: 'Pasvolgnr:<nr> <date> Transactie:<NR> Term:<nr>'.
     */
    protected function addNameIngDescription(): void
    {
        $this->row[8] = $this->row[1] . ' ' . $this->row[8];
    }

    /**
     * Remove IBAN number out of the  description
     * Default description of Description is: Naam: <OPPOS NAME> Omschrijving: <DESCRIPTION> IBAN: <OPPOS IBAN NR>.
     */
    protected function removeIBANIngDescription(): void
    {
        // Try replace the iban number with nothing. The IBAN nr is found in the third row
        $this->row[8] = preg_replace('/\sIBAN:\s' . $this->row[3] . '/', '', $this->row[8]);
    }

    /**
     * Remove name from the description (Remove everything before the description incl the word 'Omschrijving' ).
     */
    protected function removeNameIngDescription(): void
    {
        // Try remove everything before the 'Omschrijving'
        $this->row[8] = preg_replace('/.+Omschrijving: /', '', $this->row[8]);
    }

    /**
     * Copy description to name of opposite account.
     */
    private function copyDescriptionToOpposite(): void
    {
        $search = ['Naar Oranje Spaarrekening ', 'Afschrijvingen'];
        if ('' === (string)$this->row[3]) {
            $this->row[3] = trim(str_ireplace($search, '', $this->row[8]));
        }
    }
}
