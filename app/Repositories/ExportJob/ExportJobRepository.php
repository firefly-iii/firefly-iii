<?php
/**
 * ExportJobRepository.php
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

namespace FireflyIII\Repositories\ExportJob;

use FireflyIII\Models\ExportJob;
use FireflyIII\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;

/**
 * Class ExportJobRepository.
 */
class ExportJobRepository implements ExportJobRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param ExportJob $job
     * @param string    $status
     *
     * @return bool
     */
    public function changeStatus(ExportJob $job, string $status): bool
    {
        Log::debug(sprintf('Change status of job #%d to "%s"', $job->id, $status));
        $job->status = $status;
        $job->save();

        return true;
    }

    /**
     * @return ExportJob|null
     */
    public function create(): ?ExportJob
    {
        $count = 0;
        while ($count < 30) {
            $key      = Str::random(12);
            $existing = $this->findByKey($key);
            if (null === $existing) {
                $exportJob = new ExportJob;
                $exportJob->user()->associate($this->user);
                $exportJob->key    = Str::random(12);
                $exportJob->status = 'export_status_never_started';
                $exportJob->save();

                // breaks the loop:

                return $exportJob;
            }
            ++$count;
        }

        return null;
    }

    /**
     * @param ExportJob $job
     *
     * @return bool
     */
    public function exists(ExportJob $job): bool
    {
        $disk = Storage::disk('export');
        $file = $job->key . '.zip';

        return $disk->exists($file);
    }

    /**
     * @param string $key
     *
     * @return ExportJob|null
     */
    public function findByKey(string $key): ?ExportJob
    {
        /** @var ExportJob $result */
        $result = $this->user->exportJobs()->where('key', $key)->first(['export_jobs.*']);
        if (null === $result) {
            return null;
        }

        return $result;
    }

    /**
     * @param ExportJob $job
     *
     * @return string
     */
    public function getContent(ExportJob $job): string
    {
        $disk = Storage::disk('export');
        $file = $job->key . '.zip';

        try {
            $content = $disk->get($file);
        } catch (FileNotFoundException $e) {
            Log::warning(sprintf('File not found: %s', $e->getMessage()));
            $content = '';
        }

        return $content;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
