<?php
/**
 * ExportJobRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\ExportJob;

use FireflyIII\Models\ExportJob;

/**
 * Interface ExportJobRepositoryInterface
 *
 * @package FireflyIII\Repositories\ExportJob
 */
interface ExportJobRepositoryInterface
{
    /**
     * @return bool
     */
    public function cleanup(): bool;

    /**
     * @return ExportJob
     */
    public function create(): ExportJob;

    /**
     * @param string $key
     *
     * @return ExportJob|null
     */
    public function findByKey(string $key): ExportJob;

}
