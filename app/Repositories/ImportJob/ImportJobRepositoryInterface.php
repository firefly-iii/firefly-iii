<?php
/**
 * ImportJobRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\ImportJob;

use FireflyIII\Models\ImportJob;
use FireflyIII\User;

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

    /**
     * @param ImportJob $job
     * @param array     $configuration
     *
     * @return ImportJob
     */
    public function setConfiguration(ImportJob $job, array $configuration): ImportJob;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param ImportJob $job
     * @param string    $status
     *
     * @return ImportJob
     */
    public function updateStatus(ImportJob $job, string $status): ImportJob;
}
