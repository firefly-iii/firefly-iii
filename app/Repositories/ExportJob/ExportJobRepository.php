<?php
/**
 * ExportJobRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\ExportJob;

use Carbon\Carbon;
use FireflyIII\Models\ExportJob;
use FireflyIII\User;
use Illuminate\Support\Str;
use Storage;

/**
 * Class ExportJobRepository
 *
 * @package FireflyIII\Repositories\ExportJob
 */
class ExportJobRepository implements ExportJobRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param ExportJob $job
     * @param string    $status
     *
     * @return bool
     */
    public function changeStatus(ExportJob $job, string $status): bool
    {
        $job->change($status);

        return true;
    }

    /**
     * @return bool
     */
    public function cleanup(): bool
    {
        $dayAgo = Carbon::create()->subDay();
        $set    = ExportJob::where('created_at', '<', $dayAgo->format('Y-m-d H:i:s'))
                           ->whereIn('status', ['never_started', 'export_status_finished', 'export_downloaded'])
                           ->get();

        // loop set:
        /** @var ExportJob $entry */
        foreach ($set as $entry) {
            $key   = $entry->key;
            $len   = strlen($key);
            $files = scandir(storage_path('export'));
            /** @var string $file */
            foreach ($files as $file) {
                if (substr($file, 0, $len) === $key) {
                    unlink(storage_path('export') . DIRECTORY_SEPARATOR . $file);
                }
            }
            $entry->delete();
        }

        return true;
    }

    /**
     * @return ExportJob
     */
    public function create(): ExportJob
    {
        $count = 0;
        while ($count < 30) {
            $key      = Str::random(12);
            $existing = $this->findByKey($key);
            if (is_null($existing->id)) {
                $exportJob = new ExportJob;
                $exportJob->user()->associate($this->user);
                $exportJob->key    = Str::random(12);
                $exportJob->status = 'export_status_never_started';
                $exportJob->save();

                // breaks the loop:

                return $exportJob;
            }
            $count++;

        }

        return new ExportJob;

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
     * @return ExportJob
     */
    public function findByKey(string $key): ExportJob
    {
        $result = $this->user->exportJobs()->where('key', $key)->first(['export_jobs.*']);
        if (is_null($result)) {
            return new ExportJob;
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
        $disk    = Storage::disk('export');
        $file    = $job->key . '.zip';
        $content = $disk->get($file);

        return $content;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
