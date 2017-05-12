<?php
/**
 * ExporterInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Exporter;

use FireflyIII\Models\ExportJob;
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
     * @return void
     *
     */
    public function setEntries(Collection $entries);

    /**
     * @param ExportJob $job
     */
    public function setJob(ExportJob $job);

}
