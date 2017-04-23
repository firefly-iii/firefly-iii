<?php
/**
 * ImportJobRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\ImportJob;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\User;
use Illuminate\Support\Str;

/**
 * Class ImportJobRepository
 *
 * @package FireflyIII\Repositories\ImportJob
 */
class ImportJobRepository implements ImportJobRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param string $fileType
     *
     * @return ImportJob
     * @throws FireflyException
     */
    public function create(string $fileType): ImportJob
    {
        $count    = 0;
        $fileType = strtolower($fileType);
        $keys     = array_keys(config('firefly.import_formats'));
        if (!in_array($fileType, $keys)) {
            throw new FireflyException(sprintf('Cannot use type "%s" for import job.', $fileType));
        }

        while ($count < 30) {
            $key      = Str::random(12);
            $existing = $this->findByKey($key);
            if (is_null($existing->id)) {
                $importJob = new ImportJob;
                $importJob->user()->associate($this->user);
                $importJob->file_type       = $fileType;
                $importJob->key             = Str::random(12);
                $importJob->status          = 'import_status_never_started';
                $importJob->extended_status = [
                    'total_steps'  => 0,
                    'steps_done'   => 0,
                    'import_count' => 0,
                    'importTag'    => 0,
                    'errors'       => [],
                ];
                $importJob->save();

                // breaks the loop:
                return $importJob;
            }
            $count++;

        }

        return new ImportJob;
    }

    /**
     * @param string $key
     *
     * @return ImportJob
     */
    public function findByKey(string $key): ImportJob
    {
        $result = $this->user->importJobs()->where('key', $key)->first(['import_jobs.*']);
        if (is_null($result)) {
            return new ImportJob;
        }

        return $result;
    }

    /**
     * @param ImportJob $job
     * @param array     $configuration
     *
     * @return ImportJob
     */
    public function setConfiguration(ImportJob $job, array $configuration): ImportJob
    {
        $job->configuration = $configuration;
        $job->save();

        return $job;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param ImportJob $job
     * @param string    $status
     *
     * @return ImportJob
     */
    public function updateStatus(ImportJob $job, string $status): ImportJob
    {
        $job->status = $status;
        $job->save();

        return $job;
    }
}
