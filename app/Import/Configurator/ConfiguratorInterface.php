<?php
/**
 * ConfiguratorInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
     */
    public function __construct();

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
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string;

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function isJobConfigured(): bool;

    /**
     * @param ImportJob $job
     *
     * @return void
     */
    public function setJob(ImportJob $job);

}
