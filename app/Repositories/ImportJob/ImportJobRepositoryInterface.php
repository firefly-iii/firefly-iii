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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
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
     * Add message to job.
     *
     * @param ImportJob $job
     * @param string    $error
     *
     * @return ImportJob
     */
    public function addErrorMessage(ImportJob $job, string $error): ImportJob;

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
     * @param string $importProvider
     *
     * @return ImportJob
     */
    public function create(string $importProvider): ImportJob;

    /**
     * @param string $key
     *
     * @return ImportJob
     */
    public function findByKey(string $key): ImportJob;

    /**
     * Return all attachments for job.
     *
     * @param ImportJob $job
     *
     * @return Collection
     */
    public function getAttachments(ImportJob $job): Collection;

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
     * @return ImportJob
     */
    public function setExtendedStatus(ImportJob $job, array $array): ImportJob;

    /**
     * @param ImportJob $job
     * @param string    $stage
     *
     * @return ImportJob
     */
    public function setStage(ImportJob $job, string $stage): ImportJob;

    /**
     * @param ImportJob $job
     * @param string    $status
     *
     * @return ImportJob
     */
    public function setStatus(ImportJob $job, string $status): ImportJob;

    /**
     * @param ImportJob $job
     * @param int       $steps
     *
     * @return ImportJob
     */
    public function setStepsDone(ImportJob $job, int $steps): ImportJob;

    /**
     * @param ImportJob $job
     * @param Tag       $tag
     *
     * @return ImportJob
     */
    public function setTag(ImportJob $job, Tag $tag): ImportJob;

    /**
     * @param ImportJob $job
     * @param int       $count
     *
     * @return ImportJob
     */
    public function setTotalSteps(ImportJob $job, int $count): ImportJob;

    /**
     * @param ImportJob $job
     * @param array     $transactions
     *
     * @return ImportJob
     */
    public function setTransactions(ImportJob $job, array $transactions): ImportJob;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Store file.
     *
     * @param ImportJob $job
     * @param string    $name
     * @param string    $fileName
     *
     * @return MessageBag
     */
    public function storeCLIUpload(ImportJob $job, string $name, string $fileName): MessageBag;

    /**
     * Handle upload for job.
     *
     * @param ImportJob    $job
     * @param string       $name
     * @param UploadedFile $file
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function storeFileUpload(ImportJob $job, string $name, UploadedFile $file): MessageBag;

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
