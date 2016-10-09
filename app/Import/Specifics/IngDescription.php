<?php
/**
 * IngDescription.php
 * Copyright (C) 2016 https://github.com/tomwerf
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Specifics;

/**
 * Class IngDescription
 *
 * Parses the description from CSV files for Ing bank accounts.
 *
 * With Mutation 'InternetBankieren', 'Overschrijving', 'Verzamelbetaling' and
 * 'Incasso' the Name of Opposing account the Opposing IBAN number are in the
 * Description. This class will remove them, and add Name in description by
 * 'Betaalautomaat' so those are easily recognizable
 *
 * @package FireflyIII\Import\Specifics
 */
class IngDescription implements SpecificInterface
{
    /** @var  array */
    public $row;

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Create better descriptions in ING import files.';
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'ING description';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        $this->row = $row;
        if (count($this->row) >= 8) {                    // check if the array is correct
            switch ($this->row[4]) {                     // Get value for the mutation type
                case 'GT':                               // InternetBankieren
                case 'OV':                               // Overschrijving
                case 'VZ':                               // Verzamelbetaling
                case 'IC':                               // Incasso
                    $this->removeIBANIngDescription();
                    $this->removeNameIngDescription();
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
     * Otherwise the description is only: 'Pasvolgnr:<nr> <date> Transactie:<NR> Term:<nr>'
     *
     * @return bool true
     */
    protected function addNameIngDescription()
    {
        $this->row[8] = $this->row[1] . ' ' . $this->row[8];

        return true;
    }

    /**
     * Remove IBAN number out of the  description
     * Default description of Description is: Naam: <OPPOS NAME> Omschrijving: <DESCRIPTION> IBAN: <OPPOS IBAN NR>
     *
     * @return bool true
     */
    protected function removeIBANIngDescription()
    {
        // Try replace the iban number with nothing. The IBAN nr is found in the third row
        $this->row[8] = preg_replace('/\sIBAN:\s' . $this->row[3] . '/', '', $this->row[8]);

        return true;
    }

    /**
     * Remove name from the description (Remove everything before the description incl the word 'Omschrijving' )
     *
     * @return bool true
     */
    protected function removeNameIngDescription()
    {
        // Try remove everything bevore the 'Omschrijving'
        $this->row[8] = preg_replace('/.+Omschrijving: /', '', $this->row[8]);

        return true;
    }

}
