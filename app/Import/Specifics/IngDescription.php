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
 * @deprecated
 * @codeCoverageIgnore
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
        array_push($this->row); // New column for "Valutadatum"
        if (count($this->row) >= 8) {                    // check if the array is correct
            switch ($this->row[4]) {                     // Get value for the mutation type
                case 'GT':                               // InternetBankieren
                case 'OV':                               // Overschrijving
                case 'VZ':                               // Verzamelbetaling
                case 'IC':                               // Incasso
                case 'DV':                               // Divers
                    $this->removeIBANIngDescription();   // Remove "IBAN:", because it is already at "Tegenrekening"
                    $this->removeNameIngDescription();   // Remove "Naam:", because it is already at "Naam/ Omschrijving"
                    $this->removeIngDescription();       // Remove "Omschrijving", but not the value from description
                    $this->moveValutadatumDescription(); // Move "Valutadatum" from description to new column
                    $this->MoveSavingsAccount();         // Move savings account number and name
                    break;
                case 'BA':                              // Betaalautomaat
                    $this->moveValutadatumDescription(); // Move "Valutadatum" from description to new column
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
     * Move "Valutadatum" from the description to new column.
     */
    protected function moveValutadatumDescription(): void
    {
        $matches = [];
        if (preg_match('/Valutadatum: ([0-9-]+)/', $this->row[8], $matches)) {
            $this->row[9] = date("Ymd", strtotime($matches[1]));
            $this->row[8] = preg_replace('/Valutadatum: [0-9-]+/', '', $this->row[8]);
        }
    }

    /**
     * Remove IBAN number out of the  description
     * Default description of Description is: Naam: <OPPOS NAME> Omschrijving: <DESCRIPTION> IBAN: <OPPOS IBAN NR>.
     */
    protected function removeIBANIngDescription(): void
    {
        // Try replace the iban number with nothing. The IBAN nr is found in the third column
        $this->row[8] = preg_replace('/\sIBAN:\s' . $this->row[3] . '/', '', $this->row[8]);
    }

    /**
     * Remove "Omschrijving" (and NOT its value) from the description.
     */
    protected function removeIngDescription(): void
    {
        $this->row[8] = preg_replace('/Omschrijving: /', '', $this->row[8]);
    }

    /**
     * Remove "Naam" (and its value) from the description.
     */
    protected function removeNameIngDescription(): void
    {
        $this->row[8] = preg_replace('/Naam:.*?([a-zA-Z\/]+:)/', '$1', $this->row[8]);
    }

    /**
     * Move savings account number to column 1 and name to column 3.
     */
    private function MoveSavingsAccount(): void
    {
        $matches = [];

        if (preg_match('/(Naar|Van) (.*rekening) ([A-Za-z0-9]+)/', $this->row[8], $matches)) { // Search for saving acount at 'Mededelingen' column
            $this->row[1] = $this->row[1] . ' ' . $matches[2] . ' ' . $matches[3]; // Current name + Saving acount name + Acount number
            if ('' === (string) $this->row[3]) { // if Saving account number does not yet exists
                $this->row[3] = $matches[3]; // Copy savings account number
            }
            $this->row[8] = preg_replace('/(Naar|Van) (.*rekening) ([A-Za-z0-9]+)/', '', $this->row[8]); // Remove the savings account content from description
        } elseif (preg_match('/(Naar|Van) (.*rekening) ([A-Za-z0-9]+)/', $this->row[1], $matches)) { // Search for saving acount at 'Naam / Omschrijving' column
            $this->row[1] = $matches[2] . ' ' . $matches[3];  // Saving acount name + Acount number
            if ('' === (string) $this->row[3]) { // if Saving account number does not yet exists
                $this->row[3] = $matches[3]; // Copy savings account number
            }
        }

        if ('' !== (string)$this->row[3]) { // if Saving account number exists
            if (! preg_match('/[A-Za-z]/', $this->row[3])) { // if Saving account number has no characters 
                $this->row[3] = sprintf("%010d", $this->row[3]); // Make the number 10 digits
            }
        }
    }
}
