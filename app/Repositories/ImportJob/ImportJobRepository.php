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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\ImportJob;

use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Str;
use Log;
use SplFileObject;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ImportJobRepository.
 */
class ImportJobRepository implements ImportJobRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param ImportJob $job
     * @param int       $index
     * @param string    $error
     *
     * @return ImportJob
     */
    public function addError(ImportJob $job, int $index, string $error): ImportJob
    {
        $extended                     = $this->getExtendedStatus($job);
        $extended['errors'][$index][] = $error;

        return $this->setExtendedStatus($job, $extended);
    }

    /**
     * @param ImportJob $job
     * @param int       $steps
     *
     * @return ImportJob
     */
    public function addStepsDone(ImportJob $job, int $steps = 1): ImportJob
    {
        $status         = $this->getExtendedStatus($job);
        $status['done'] += $steps;
        Log::debug(sprintf('Add %d to steps done for job "%s" making steps done %d', $steps, $job->key, $status['done']));

        return $this->setExtendedStatus($job, $status);
    }

    /**
     * @param ImportJob $job
     * @param int       $steps
     *
     * @return ImportJob
     */
    public function addTotalSteps(ImportJob $job, int $steps = 1): ImportJob
    {
        $extended          = $this->getExtendedStatus($job);
        $total             = $extended['steps'] ?? 0;
        $total             += $steps;
        $extended['steps'] = $total;

        return $this->setExtendedStatus($job, $extended);

    }

    /**
     * Return number of imported rows with this hash value.
     *
     * @param string $hash
     *
     * @return int
     */
    public function countByHash(string $hash): int
    {
        $json  = json_encode($hash);
        $count = TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
                                       ->where('data', $json)
                                       ->where('name', 'importHash')
                                       ->count();

        return intval($count);
    }

    /**
     * @param string $type
     *
     * @return ImportJob
     *
     * @throws FireflyException
     */
    public function create(string $type): ImportJob
    {
        $count = 0;
        $type  = strtolower($type);

        while ($count < 30) {
            $key      = Str::random(12);
            $existing = $this->findByKey($key);
            if (null === $existing->id) {
                $importJob = new ImportJob;
                $importJob->user()->associate($this->user);
                $importJob->file_type       = $type;
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
            ++$count;
        }
        throw new FireflyException('Could not create an import job with a unique key after 30 tries.');
    }

    /**
     * @param string $key
     *
     * @return ImportJob
     */
    public function findByKey(string $key): ImportJob
    {
        /** @var ImportJob $result */
        $result = $this->user->importJobs()->where('key', $key)->first(['import_jobs.*']);
        if (null === $result) {
            return new ImportJob;
        }

        return $result;
    }

    /**
     * Return configuration of job.
     *
     * @param ImportJob $job
     *
     * @return array
     */
    public function getConfiguration(ImportJob $job): array
    {
        $config = $job->configuration;
        if (is_array($config)) {
            return $config;
        }

        return [];
    }

    /**
     * Return extended status of job.
     *
     * @param ImportJob $job
     *
     * @return array
     */
    public function getExtendedStatus(ImportJob $job): array
    {
        $status = $job->extended_status;
        if (is_array($status)) {
            return $status;
        }

        return [];
    }

    /**
     * @param ImportJob $job
     *
     * @return string
     */
    public function getStatus(ImportJob $job): string
    {
        return $job->status;
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
                'Uploaded configuration file',
                ['name' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'mime' => $file->getClientMimeType()]
            );

            $configFileObject = new SplFileObject($file->getRealPath());
            $configRaw        = $configFileObject->fread($configFileObject->getSize());
            $configuration    = json_decode($configRaw, true);
            Log::debug(sprintf('Raw configuration is %s', $configRaw));
            if (null !== $configuration && is_array($configuration)) {
                Log::debug('Found configuration', $configuration);
                $this->setConfiguration($job, $configuration);
            }
            if (null === $configuration) {
                Log::error('Uploaded configuration is NULL');
            }
            if (false === $configuration) {
                Log::error('Uploaded configuration is FALSE');
            }
        }

        return true;
    }

    /**
     * @param ImportJob         $job
     * @param null|UploadedFile $file
     *
     * @return bool
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function processFile(ImportJob $job, ?UploadedFile $file): bool
    {
        if (is_null($file)) {
            return false;
        }
        /** @var UserRepositoryInterface $repository */
        $repository       = app(UserRepositoryInterface::class);
        $newName          = sprintf('%s.upload', $job->key);
        $uploaded         = new SplFileObject($file->getRealPath());
        $content          = trim($uploaded->fread($uploaded->getSize()));
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
        Log::debug(sprintf('Incoming config for job "%s" is: ', $job->key), $configuration);
        $currentConfig      = $job->configuration;
        $newConfig          = array_merge($currentConfig, $configuration);
        $job->configuration = $newConfig;
        $job->save();
        Log::debug(sprintf('Set config of job "%s" to: ', $job->key), $newConfig);

        return $job;
    }

    /**
     * @param ImportJob $job
     * @param array     $array
     *
     * @return ImportJob
     */
    public function setExtendedStatus(ImportJob $job, array $array): ImportJob
    {
        // remove 'errors' because it gets larger and larger and larger...
        $display = $array;
        unset($display['errors']);
        Log::debug(sprintf('Incoming extended status for job "%s" is (except errors): ', $job->key), $display);
        $currentStatus        = $job->extended_status;
        $newStatus            = array_merge($currentStatus, $array);
        $job->extended_status = $newStatus;
        $job->save();

        // remove 'errors' because it gets larger and larger and larger...
        unset($newStatus['errors']);
        Log::debug(sprintf('Set extended status of job "%s" to (except errors): ', $job->key), $newStatus);

        return $job;
    }

    /**
     * @param ImportJob $job
     * @param string    $status
     *
     * @return ImportJob
     */
    public function setStatus(ImportJob $job, string $status): ImportJob
    {
        $job->status = $status;
        $job->save();

        return $job;
    }

    /**
     * @param ImportJob $job
     * @param int       $steps
     *
     * @return ImportJob
     */
    public function setStepsDone(ImportJob $job, int $steps): ImportJob
    {
        $status         = $this->getExtendedStatus($job);
        $status['done'] = $steps;
        Log::debug(sprintf('Set steps done for job "%s" to %d', $job->key, $steps));

        return $this->setExtendedStatus($job, $status);
    }

    /**
     * @param ImportJob $job
     * @param int       $count
     *
     * @return ImportJob
     */
    public function setTotalSteps(ImportJob $job, int $count): ImportJob
    {
        $status          = $this->getExtendedStatus($job);
        $status['steps'] = $count;
        Log::debug(sprintf('Set total steps for job "%s" to %d', $job->key, $count));

        return $this->setExtendedStatus($job, $status);
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

    /**
     * Return import file content.
     *
     * @param ImportJob $job
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function uploadFileContents(ImportJob $job): string
    {
        return $job->uploadFileContents();
    }
}
