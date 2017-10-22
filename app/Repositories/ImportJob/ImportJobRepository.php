<?php
/**
 * ImportJobRepository.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\ImportJob;

use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Str;
use Log;
use SplFileObject;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
                $importJob->status          = 'new';
                $importJob->configuration   = [];
                $importJob->extended_status = [
                    'steps'  => 0,
                    'done'   => 0,
                    'tag'    => 0,
                    'errors' => [],
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
     * @param ImportJob    $job
     * @param UploadedFile $file
     *
     * @return bool
     */
    public function processConfiguration(ImportJob $job, UploadedFile $file): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        // demo user's configuration upload is ignored completely.
        if (!$repository->hasRole($this->user, 'demo')) {
            Log::debug(
                'Uploaded configuration file', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'mime' => $file->getClientMimeType()]
            );

            $configFileObject = new SplFileObject($file->getRealPath());
            $configRaw        = $configFileObject->fread($configFileObject->getSize());
            $configuration    = json_decode($configRaw, true);

            if (!is_null($configuration) && is_array($configuration)) {
                Log::debug('Found configuration', $configuration);
                $this->setConfiguration($job, $configuration);
            }
        }

        return true;
    }

    /**
     * @param ImportJob    $job
     * @param UploadedFile $file
     *
     * @return mixed
     */
    public function processFile(ImportJob $job, UploadedFile $file): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository       = app(UserRepositoryInterface::class);
        $newName          = sprintf('%s.upload', $job->key);
        $uploaded         = new SplFileObject($file->getRealPath());
        $content          = $uploaded->fread($uploaded->getSize());
        $contentEncrypted = Crypt::encrypt($content);
        $disk             = Storage::disk('upload');


        // user is demo user, replace upload with prepared file.
        if ($repository->hasRole($this->user, 'demo')) {
            $stubsDisk        = Storage::disk('stubs');
            $content          = $stubsDisk->get('demo-import.csv');
            $contentEncrypted = Crypt::encrypt($content);
            $disk->put($newName, $contentEncrypted);
            Log::debug('Replaced upload with demo file.');

            // also set up prepared configuration.
            $configuration = json_decode($stubsDisk->get('demo-configuration.json'), true);
            $this->setConfiguration($job, $configuration);
            Log::debug('Set configuration for demo user', $configuration);
        }

        if (!$repository->hasRole($this->user, 'demo')) {
            // user is not demo, process original upload:
            $disk->put($newName, $contentEncrypted);
            Log::debug('Uploaded file', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'mime' => $file->getClientMimeType()]);
        }

        return true;
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
