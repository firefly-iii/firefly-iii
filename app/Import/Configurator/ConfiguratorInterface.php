<?php
/**
 * ConfiguratorInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Configurator;

use FireflyIII\Models\ImportJob;

/**
 * Interface ConfiguratorInterface
 *
 * @package FireflyIII\Import\Configurator
 */
interface ConfiguratorInterface
{
    /**
     * ConfiguratorInterface constructor.
     *
     * @param ImportJob $job
     */
    public function __construct(ImportJob $job);

    /**
     * Store any data from the $data array into the job.
     *
     * @param array $data
     *
     * @return bool
     */
    public function configureJob(array $data): bool;

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     */
    public function getNextData(): array;

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     */
    public function getNextView(): string;

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function isJobConfigured(): bool;

}