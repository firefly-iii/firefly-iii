<?php
/**
 * ImportJobRepositoryInterface.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\ImportJob;

use FireflyIII\Models\ImportJob;
use FireflyIII\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface ImportJobRepositoryInterface.
 */
interface ImportJobRepositoryInterface
{

    /**
     * @param ImportJob $job
     * @param int       $index
     * @param string    $error
     *
     * @return ImportJob
     */
    public function addError(ImportJob $job, int $index, string $error): ImportJob;

    /**
     * @param ImportJob $job
     * @param int       $steps
     *
     * @return ImportJob
     */
    public function addStepsDone(ImportJob $job, int $steps = 1): ImportJob;

    /**
     * @param ImportJob $job
     * @param int       $steps
     *
     * @return ImportJob
     */
    public function addTotalSteps(ImportJob $job, int $steps = 1): ImportJob;

    /**
     * Return number of imported rows with this hash value.
     *
     * @param string $hash
     *
     * @return int
     */
    public function countByHash(string $hash): int;

    /**
     * @param string $type
     *
     * @return ImportJob
     */
    public function create(string $type): ImportJob;

    /**
     * @param string $key
     *
     * @return ImportJob
     */
    public function findByKey(string $key): ImportJob;

    /**
     * Return configuration of job.
     *
     * @param ImportJob $job
     *
     * @return array
     */
    public function getConfiguration(ImportJob $job): array;

    /**
     * Return extended status of job.
     *
     * @param ImportJob $job
     *
     * @return array
     */
    public function getExtendedStatus(ImportJob $job): array;

    /**
     * @param ImportJob $job
     *
     * @return string
     */
    public function getStatus(ImportJob $job);

    /**
     * @param ImportJob    $job
     * @param UploadedFile $file
     *
     * @return bool
     */
    public function processConfiguration(ImportJob $job, UploadedFile $file): bool;

    /**
     * @param ImportJob         $job
     * @param null|UploadedFile $file
     *
     * @return bool
     */
    public function processFile(ImportJob $job, ?UploadedFile $file): bool;

    /**
     * @param ImportJob $job
     * @param array     $configuration
     *
     * @return ImportJob
     */
    public function setConfiguration(ImportJob $job, array $configuration): ImportJob;

    /**
     * @param ImportJob $job
     * @param array     $array
     *
     * @return void
     */
    public function setExtendedStatus(ImportJob $job, array $array): ImportJob;

    /**
     * @param ImportJob $job
     * @param string    $status
     *
     * @return ImportJob
     */
    public function setStatus(ImportJob $job, string $status): ImportJob;

    /**
     * @param ImportJob $job
     * @param int       $count
     *
     * @return ImportJob
     */
    public function setStepsDone(ImportJob $job, int $steps): ImportJob;

    /**
     * @param ImportJob $job
     * @param int       $count
     *
     * @return ImportJob
     */
    public function setTotalSteps(ImportJob $job, int $count): ImportJob;

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

    /**
     * Return import file content.
     *
     * @param ImportJob $job
     *
     * @return string
     */
    public function uploadFileContents(ImportJob $job): string;
}
