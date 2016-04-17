<?php
declare(strict_types = 1);
/**
 * ExporterInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Exporter;

use Illuminate\Support\Collection;

/**
 * Interface ExporterInterface
 *
 * @package FireflyIII\Export\Exporter
 */
interface ExporterInterface
{
    /**
     * @return Collection
     */
    public function getEntries(): Collection;

    /**
     * @return string
     */
    public function getFileName(): string;

    /**
     * @return bool
     */
    public function run(): bool;

    /**
     * @param Collection $entries
     *
     */
    public function setEntries(Collection $entries);

}
