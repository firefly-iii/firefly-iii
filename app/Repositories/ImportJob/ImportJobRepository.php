<?php
/**
 * ImportJobRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\ImportJob;

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
     * ExportJobRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $fileType
     *
     * @return ImportJob
     */
    public function create(string $fileType): ImportJob
    {
        $count = 0;
        while ($count < 30) {
            $key      = Str::random(12);
            $existing = $this->findByKey($key);
            if (is_null($existing->id)) {
                $importJob = new ImportJob;
                $importJob->user()->associate($this->user);
                $importJob->file_type       = $fileType;
                $importJob->key             = Str::random(12);
                $importJob->status          = 'import_status_never_started';
                $importJob->extended_status = ['total_steps' => 0, 'steps_done' => 0, 'errors' => [], 'import_count' => 0];
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
}
