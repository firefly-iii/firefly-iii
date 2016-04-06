<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Specifix;

use Log;

/**
 * Parses the description from txt files for ABN AMRO bank accounts.
 *
 * Based on the logic as described in the following Gist:
 * https://gist.github.com/vDorst/68d555a6a90f62fec004
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class AbnAmroDescription extends Specifix implements SpecifixInterface
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;

    /**
     * AbnAmroDescription constructor.
     */
    public function __construct()
    {
        $this->setProcessorType(self::POST_PROCESSOR);
    }


    /**
     * @return array
     */
    public function fix()
    {
        // Try to parse the description in known formats.
        $parsed = $this->parseSepaDescription() || $this->parseTRTPDescription() || $this->parseGEABEADescription() || $this->parseABNAMRODescription();

        // If the description could not be parsed, specify an unknown opposing 
        // account, as an opposing account is required
        if (!$parsed) {
            $this->data['opposing-account-name'] = trans('firefly.unknown');
        }

        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array $row
     */
    public function setRow(array $row)
    {
        $this->row = $row;
    }

    /**
     * Parses the current description with costs from ABN AMRO itself
     *
     * @return bool true if the description is GEA/BEA-format, false otherwise
     */
    protected function parseABNAMRODescription()
    {
        // See if the current description is formatted in ABN AMRO format
        if (preg_match('/ABN AMRO.{24} (.*)/', $this->data['description'], $matches)) {
            Log::debug('AbnAmroSpecifix: Description is structured as costs from ABN AMRO itself.');

            $this->data['opposing-account-name'] = 'ABN AMRO';
            $this->data['description']           = $matches[1];

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
        if (preg_match('/([BG]EA) +(NR:[a-zA-Z:0-9]+) +([0-9.\/]+) +([^,]*)/', $this->data['description'], $matches)) {
            Log::debug('AbnAmroSpecifix: Description is structured as GEA or BEA format.');

            // description and opposing account will be the same.
            $this->data['opposing-account-name'] = $matches[4];

            if ($matches[1] == 'GEA') {
                $this->data['description'] = 'GEA ' . $matches[4];
            } else {
                $this->data['description'] = $matches[4];
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
        if (preg_match('/^SEPA(.{28})/', $this->data['description'], $matches)) {
            Log::debug('AbnAmroSpecifix: Description is structured as SEPA plain description.');

            $type           = $matches[1];
            $reference      = '';
            $name           = '';
            $newDescription = '';

            // SEPA plain descriptions contain several key-value pairs, split by a colon
            preg_match_all('/([A-Za-z]+(?=:\s)):\s([A-Za-z 0-9._#-]+(?=\s|$))/', $this->data['description'], $matches, PREG_SET_ORDER);

            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $key   = $match[1];
                    $value = trim($match[2]);
                    Log::debug('SEPA: ' . $key . ' - ' . $value);
                    switch (strtoupper($key)) {
                        case 'OMSCHRIJVING':
                            $newDescription = $value;
                            break;
                        case 'NAAM':
                            $this->data['opposing-account-name'] = $name = $value;
                            break;
                        case 'KENMERK':
                            $reference = $value;
                            break;
                        case 'IBAN':
                            $this->data['opposing-account-iban'] = $value;
                            break;
                        default:
                            // Ignore the rest
                    }
                }
            }

            // Set a new description for the current transaction. If none was given
            // set the description to type, name and reference
            if ($newDescription) {
                $this->data['description'] = $newDescription;
            } else {
                $this->data['description'] = sprintf('%s - %s (%s)', $type, $name, $reference);
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
        if (preg_match_all('!\/([A-Z]{3,4})\/([^/]*)!', $this->data['description'], $matches, PREG_SET_ORDER)) {
            Log::debug('AbnAmroSpecifix: Description is structured as TRTP format.');

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
                            $this->data['opposing-account-name'] = $name = $value;
                            break;
                        case 'REMI':
                            $newDescription = $value;
                            break;
                        case 'IBAN':
                            $this->data['opposing-account-iban'] = $value;
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
                if ($newDescription) {
                    $this->data['description'] = $newDescription;
                } else {
                    $this->data['description'] = sprintf('%s - %s (%s)', $type, $name, $reference);
                }
            }

            return true;
        }

        return false;
    }

}
