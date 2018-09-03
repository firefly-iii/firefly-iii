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

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\ExportJob;
use FireflyIII\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Log;
use Storage;

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
        if ('testing' === env('APP_ENV')) {
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
     * @return bool
     */
    public function cleanup(): bool
    {
        $dayAgo = Carbon::now()->subDay();
        $set    = ExportJob::where('created_at', '<', $dayAgo->format('Y-m-d H:i:s'))
                           ->whereIn('status', ['never_started', 'export_status_finished', 'export_downloaded'])
                           ->get();

        // loop set:
        /** @var ExportJob $entry */
        foreach ($set as $entry) {
            $key   = $entry->key;
            $files = scandir(storage_path('export'), SCANDIR_SORT_NONE);
            /** @var string $file */
            foreach ($files as $file) {
                if (0 === strpos($file, $key)) {
                    unlink(storage_path('export') . DIRECTORY_SEPARATOR . $file);
                }
            }
            try {
                $entry->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Could not delete object: %s', $e->getMessage()));
            }
        }

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
