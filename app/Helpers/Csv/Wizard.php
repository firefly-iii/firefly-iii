<?php
namespace FireflyIII\Helpers\Csv;

use Auth;
use Config;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use League\Csv\Reader;
use Session;

/**
 * Class Wizard
 *
 * @package FireflyIII\Helpers\Csv
 */
class Wizard implements WizardInterface
{


    /**
     * @param Reader $reader
     * @param array  $map
     * @param bool   $hasHeaders
     *
     * @return array
     */
    public function getMappableValues($reader, array $map, $hasHeaders)
    {
        $values = [];
        /*
         * Loop over the CSV and collect mappable data:
         */
        foreach ($reader as $index => $row) {
            if (($hasHeaders && $index > 1) || !$hasHeaders) {
                // collect all map values
                foreach ($map as $column => $irrelevant) {
                    // check if $irrelevant is mappable!
                    $values[$column][] = $row[$column];
                }
            }
        }
        /*
         * Make each one unique.
         */
        foreach ($values as $column => $found) {
            $values[$column] = array_unique($found);
        }

        return $values;
    }


    /**
     * @param array $roles
     * @param mixed $map
     *
     * @return array
     */
    public function processSelectedMapping(array $roles, $map)
    {
        $configRoles = Config::get('csv.roles');
        $maps        = [];


        if (is_array($map)) {
            foreach ($map as $index => $field) {
                if (isset($roles[$index])) {
                    $name = $roles[$index];
                    if ($configRoles[$name]['mappable']) {
                        $maps[$index] = $name;
                    }
                }
            }
        }

        return $maps;

    }

    /**
     * @param mixed $input
     *
     * @return array
     */
    public function processSelectedRoles($input)
    {
        $roles = [];


        /*
         * Store all rows for each column:
         */
        if (is_array($input)) {
            foreach ($input as $index => $role) {
                if ($role != '_ignore') {
                    $roles[$index] = $role;
                }
            }
        }

        return $roles;
    }

    /**
     * @param array $fields
     *
     * @return bool
     */
    public function sessionHasValues(array $fields)
    {
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param array $map
     *
     * @return array
     * @throws FireflyException
     */
    public function showOptions(array $map)
    {
        $dataGrabber = new DataGrabber;
        $options     = [];
        foreach ($map as $index => $columnRole) {

            /*
             * Depending on the column role, get the relevant data from the database.
             * This needs some work to be optimal.
             */
            switch ($columnRole) {
                default:
                    throw new FireflyException('Cannot map field of type "' . $columnRole . '".');
                    break;
                case 'account-iban':
                    $set = $dataGrabber->getAssetAccounts();
                    break;
                case 'currency-code':
                    $set = $dataGrabber->getCurrencies();
                    break;
            }

            /*
             * Make select list kind of thing:
             */

            $options[$index] = $set;
        }


        return $options;
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function storeCsvFile($path)
    {
        $time             = str_replace(' ', '-', microtime());
        $fileName         = 'csv-upload-' . Auth::user()->id . '-' . $time . '.csv.encrypted';
        $fullPath         = storage_path('upload') . DIRECTORY_SEPARATOR . $fileName;
        $content          = file_get_contents($path);
        $contentEncrypted = Crypt::encrypt($content);
        file_put_contents($fullPath, $contentEncrypted);

        return $fullPath;


    }
}