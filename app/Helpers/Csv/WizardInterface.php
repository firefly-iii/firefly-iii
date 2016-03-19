<?php
declare(strict_types = 1);

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
    public function getMappableValues(Reader $reader, array $map, bool $hasHeaders);

    /**
     * @param array $roles
     * @param array $map
     *
     * @return array
     */
    public function processSelectedMapping(array $roles, array $map);

    /**
     * @param array $input
     *
     * @return array
     */
    public function processSelectedRoles(array $input);

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
     * @param string $path
     *
     * @return string
     */
    public function storeCsvFile(string $path);

}
