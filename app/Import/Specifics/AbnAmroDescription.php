<?php
/**
 * AbnAmroDescription.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Specifics;

/**
 * Class AbnAmroDescription
 *
 * Parses the description from txt files for ABN AMRO bank accounts.
 *
 * Based on the logic as described in the following Gist:
 * https://gist.github.com/vDorst/68d555a6a90f62fec004
 *
 * @package FireflyIII\Import\Specifics
 */
class AbnAmroDescription implements SpecificInterface
{
    /** @var  array */
    public $row;

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Fixes possible problems with ABN Amro descriptions.';
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'ABN Amro description';
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array
    {
        $this->row = $row;

        if (!isset($row[7])) {
            return $row;
        }

        // Try to parse the description in known formats.
        $parsed = $this->parseSepaDescription() || $this->parseTRTPDescription() || $this->parseGEABEADescription() || $this->parseABNAMRODescription();


        // If the description could not be parsed, specify an unknown opposing
        // account, as an opposing account is required
        if (!$parsed) {
            $this->row[8] = trans('firefly.unknown'); // opposing-account-name
        }

        return $this->row;
    }

    /**
     * Parses the current description with costs from ABN AMRO itself
     *
     * @return bool true if the description is GEA/BEA-format, false otherwise
     */
    protected function parseABNAMRODescription()
    {
        // See if the current description is formatted in ABN AMRO format
        if (preg_match('/ABN AMRO.{24} (.*)/', $this->row[7], $matches)) {

            $this->row[8] = 'ABN AMRO'; // this one is new (opposing account name)
            $this->row[7] = $matches[1]; // this is the description

            return true;
        }

        return false;
    }

    /**
     * Parses the current description in GEA/BEA format
     *
     * @return bool true if the description is GEA/BEAformat, false otherwise
     */
    protected function parseGEABEADescription()
    {
        // See if the current description is formatted in GEA/BEA format
        if (preg_match('/([BG]EA) +(NR:[a-zA-Z:0-9]+) +([0-9.\/]+) +([^,]*)/', $this->row[7], $matches)) {

            // description and opposing account will be the same.
            $this->row[8] = $matches[4]; // 'opposing-account-name'
            $this->row[7] = $matches[4]; // 'description'

            if ($matches[1] == 'GEA') {
                $this->row[7] = 'GEA ' . $matches[4]; // 'description'
            }

            return true;
        }

        return false;
    }

    /**
     * Parses the current description in SEPA format
     *
     * @return bool true if the description is SEPA format, false otherwise
     */
    protected function parseSepaDescription()
    {
        // See if the current description is formatted as a SEPA plain description
        if (preg_match('/^SEPA(.{28})/', $this->row[7], $matches)) {

            $type           = $matches[1];
            $reference      = '';
            $name           = '';
            $newDescription = '';

            // SEPA plain descriptions contain several key-value pairs, split by a colon
            preg_match_all('/([A-Za-z]+(?=:\s)):\s([A-Za-z 0-9._#-]+(?=\s|$))/', $this->row[7], $matches, PREG_SET_ORDER);

            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $key   = $match[1];
                    $value = trim($match[2]);
                    switch (strtoupper($key)) {
                        case 'OMSCHRIJVING':
                            $newDescription = $value;
                            break;
                        case 'NAAM':
                            $this->row[8] = $value;
                            $name         = $value;
                            break;
                        case 'KENMERK':
                            $reference = $value;
                            break;
                        case 'IBAN':
                            $this->row[9] = $value;
                            break;
                        default:
                            // Ignore the rest
                    }
                }
            }

            // Set a new description for the current transaction. If none was given
            // set the description to type, name and reference
            $this->row[7] = $newDescription;
            if (strlen($newDescription) === 0) {
                $this->row[7] = sprintf('%s - %s (%s)', $type, $name, $reference);
            }

            return true;
        }

        return false;
    }

    /**
     * Parses the current description in TRTP format
     *
     * @return bool true if the description is TRTP format, false otherwise
     */
    protected function parseTRTPDescription()
    {
        // See if the current description is formatted in TRTP format
        if (preg_match_all('!\/([A-Z]{3,4})\/([^/]*)!', $this->row[7], $matches, PREG_SET_ORDER)) {

            $type           = '';
            $name           = '';
            $reference      = '';
            $newDescription = '';

            // Search for properties specified in the TRTP format. If no description
            // is provided, use the type, name and reference as new description
            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $key   = $match[1];
                    $value = trim($match[2]);

                    switch (strtoupper($key)) {
                        case 'NAME':
                            $this->row[8] = $name = $value;
                            break;
                        case 'REMI':
                            $newDescription = $value;
                            break;
                        case 'IBAN':
                            $this->row[9] = $value;
                            break;
                        case 'EREF':
                            $reference = $value;
                            break;
                        case 'TRTP':
                            $type = $value;
                            break;
                        default:
                            // Ignore the rest
                    }
                }

                // Set a new description for the current transaction. If none was given
                // set the description to type, name and reference
                $this->row[7] = $newDescription;
                if (strlen($newDescription) === 0) {
                    $this->row[7] = sprintf('%s - %s (%s)', $type, $name, $reference);
                }
            }

            return true;
        }

        return false;
    }


}
