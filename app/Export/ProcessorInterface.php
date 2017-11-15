<?php
/**
 * ProcessorInterface.php
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

namespace FireflyIII\Export;

use Illuminate\Support\Collection;

/**
 * Interface ProcessorInterface.
 */
interface ProcessorInterface
{
    /**
     * Processor constructor.
     */
    public function __construct();

    /**
     * @return bool
     */
    public function collectAttachments(): bool;

    /**
     * @return bool
     */
    public function collectJournals(): bool;

    /**
     * @return bool
     */
    public function collectOldUploads(): bool;

    /**
     * @return bool
     */
    public function convertJournals(): bool;

    /**
     * @return bool
     */
    public function createZipFile(): bool;

    /**
     * @return bool
     */
    public function exportJournals(): bool;

    /**
     * @return Collection
     */
    public function getFiles(): Collection;

    /**
     * @param array $settings
     */
    public function setSettings(array $settings);
}
