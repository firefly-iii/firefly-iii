<?php
/**
 * ImportJobRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\ImportJob;

use FireflyIII\Models\ImportJob;

/**
 * Interface ImportJobRepositoryInterface
 *
 * @package FireflyIII\Repositories\ImportJob
 */
interface ImportJobRepositoryInterface
{
    /**
     * @param string $fileType
     *
     * @return ImportJob
     */
    public function create(string $fileType): ImportJob;

    /**
     * @param string $key
     *
     * @return ImportJob
     */
    public function findByKey(string $key): ImportJob;
}