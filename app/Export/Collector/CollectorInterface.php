<?php
/**
 * CollectorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * @return Collection
     */
    public function getFiles(): Collection;

    /**
     * @return bool
     */
    public function run(): bool;

    /**
     * @param Collection $files
     *
     */
    public function setFiles(Collection $files);

}
