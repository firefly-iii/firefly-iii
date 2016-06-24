<?php
/**
 * ImporterInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Importer;

use FireflyIII\Import\Role\Map;
use FireflyIII\Models\ImportJob;

/**
 * Interface ImporterInterface
 *
 * @package FireflyIII\Import\Importer
 */
interface ImporterInterface
{
    /**
     * After uploading, and after setJob(), prepare anything that is
     * necessary for the configure() line.
     *
     * @return bool
     */
    public function configure(): bool;

    /**
     * Returns any data necessary to do the configuration.
     *
     * @return array
     */
    public function getConfigurationData(): array;

    /**
     * @param array $data
     *
     * @return bool
     */
    public function saveImportConfiguration(array $data): bool;

    /**
     * Returns a Map thing used to allow the user to
     * define roles for each entry.
     *
     * @return Map
     */
    public function prepareRoles(): Map;

    /**
     * @param ImportJob $job
     *
     */
    public function setJob(ImportJob $job);
}