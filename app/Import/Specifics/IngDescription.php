<?php
/**
 * IngDescription.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Specifics;

/**
 * Class IngDescription
 *
 * Parses the description from CSV files for Ing bank accounts.
 *
 * With Mutation 'InternetBankieren', 'Incasso' and Overschrijving remove Name and IBAN from description
 * Add Name in description by 'Betaalautomaat' so those are easily recognizable
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
        return 'Fixes Ing descriptions.';
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'Ing description';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        $this->row = $row;
        if (count($this->row) >= 8) { // check if the array is correct
            switch ($this->row[4]) { //update Decription only for the next Mutations
                case 'GT': // InternetBanieren
                case 'OV': // OV
                case 'VZ': // Verzamelbetaling
                case 'IC'://Incasso
                    $this->removeIBANIngDescription();
                    $this->removeNameIngDescription();
                    break;
                case 'BA' ://Betaalautomaat
                    $this->addNameIngDescription();
                    break;
            }
        }

//var_dump($this->row);die;

        return $this->row;
    }

    /**
     * Parses the current description without the IBAN in the description 
     *
     * @return always true
     */
    protected function removeIBANIngDescription()
    {
        // Try remove the iban number from the third cell 'IBAN: NL00XXXX0000000 '
        $this->row[8] = preg_replace('/\sIBAN:\s'.$this->row[3].'/', '', $this->row[8]);
        return true;
    }


    /**
     * Parses the current description without the name 
     *
     * @return bool always true
     */
    protected function removeNameIngDescription()
    {
        // Try remove the name
        $this->row[8] = preg_replace('/.+Omschrijving: /', '', $this->row[8]);
        return true;
    }


    /**
     * Parses the current description without the name and IBAN
     *
     * @return bool true if the description is GEA/BEA-format, false otherwise
     */
    protected function addNameIngDescription()
    {
        $this->row[8] = $this->row[1]. " " . $this->row[8]; 
        return true;
    }

}
