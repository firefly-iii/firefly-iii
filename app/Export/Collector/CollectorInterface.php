<?php
declare(strict_types = 1);
/**
 * CollectorInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Collector;
use Illuminate\Support\Collection;

/**
 * Interface CollectorInterface
 *
 * @package FireflyIII\Export\Collector
 */
interface CollectorInterface
{
    /**
     * @return bool
     */
    public function run();

    /**
     * @return Collection
     */
    public function getFiles();

    /**
     * @param Collection $files
     *
     */
    public function setFiles(Collection $files);

}