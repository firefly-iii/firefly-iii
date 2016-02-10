<?php
declare(strict_types = 1);
/**
 * ExporterInterface.php
 * Copyright (C) 2016 Sander Dorigo
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
    public function getEntries();

    /**
     *
     */
    public function run();

    /**
     * @param Collection $entries
     *
     */
    public function setEntries(Collection $entries);

    /**
     * @return string
     */
    public function getFileName();

}
