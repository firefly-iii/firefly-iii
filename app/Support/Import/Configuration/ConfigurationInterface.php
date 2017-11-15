<?php
/**
 * ConfigurationInterface.php
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
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string;

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
