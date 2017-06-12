<?php
/**
 * ConfigurationInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Configuration;

use FireflyIII\Models\ImportJob;

/**
 * Class ConfigurationInterface
 *
 * @package FireflyIII\Support\Import\Configuration
 */
interface ConfigurationInterface
{
    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * @param ImportJob $job
     *
     * @return ConfigurationInterface
     */
    public function setJob(ImportJob $job);

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool;

}