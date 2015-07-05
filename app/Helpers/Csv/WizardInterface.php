<?php

namespace FireflyIII\Helpers\Csv;
use League\Csv\Reader;

/**
 * Interface WizardInterface
 *
 * @package FireflyIII\Helpers\Csv
 */
interface WizardInterface
{
    /**
     * @param Reader $reader
     * @param array  $map
     * @param bool   $hasHeaders
     *
     * @return array
     */
    public function getMappableValues($reader, array $map, $hasHeaders);

    /**
     * @param array $roles
     * @param mixed $map
     *
     * @return array
     */
    public function processSelectedMapping(array $roles, $map);

    /**
     * @param mixed $input
     *
     * @return array
     */
    public function processSelectedRoles($input);

    /**
     * @param array $fields
     *
     * @return bool
     */
    public function sessionHasValues(array $fields);

    /**
     * @param array $map
     *
     * @return array
     */
    public function showOptions(array $map);

    /**
     * @param $path
     *
     * @return string
     */
    public function storeCsvFile($path);

}