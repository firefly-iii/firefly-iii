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
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\MessageBag;
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
    /** @var int */
    private $maxUploadSize;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    protected $uploadDisk;

    public function __construct()
    {
        $this->maxUploadSize = (int)config('firefly.maxUploadSize');
        $this->uploadDisk    = Storage::disk('upload');
    }

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
        $total             = (int)($extended['steps'] ?? 0);
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

        return (int)$count;
    }

    /**
     * @param string $importProvider
     *
     * @return ImportJob
     *
     * @throws FireflyException
     */
    public function create(string $importProvider): ImportJob
    {
        $count          = 0;
        $importProvider = strtolower($importProvider);

        while ($count < 30) {
            $key      = Str::random(12);
            $existing = $this->findByKey($key);
            if (null === $existing->id) {
                $importJob = ImportJob::create(
                    [
                        'user_id'         => $this->user->id,
                        'tag_id'          => null,
                        'provider'        => $importProvider,
                        'file_type'       => '',
                        'key'             => Str::random(12),
                        'status'          => 'new',
                        'stage'           => 'new',
                        'configuration'   => [],
                        'extended_status' => [],
                        'transactions'    => [],
                        'errors'          => [],
                    ]
                );

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
        if (\is_array($config)) {
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
        if (\is_array($status)) {
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
            if (null !== $configuration && \is_array($configuration)) {
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
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function processFile(ImportJob $job, ?UploadedFile $file): bool
    {
        if (null === $file) {
            return false;
        }
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $newName    = sprintf('%s.upload', $job->key);
        $uploaded   = new SplFileObject($file->getRealPath());
        $content    = trim($uploaded->fread($uploaded->getSize()));

        // verify content:
        $result = mb_detect_encoding($content, 'UTF-8', true);
        if ($result === false) {
            Log::error(sprintf('Cannot detect encoding for uploaded import file "%s".', $file->getClientOriginalName()));

            return false;
        }
        if ($result !== 'ASCII' && $result !== 'UTF-8') {
            Log::error(sprintf('Uploaded import file is %s instead of UTF8!', var_export($result, true)));

            return false;
        }

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
        $currentStatus        = $job->extended_status;
        $newStatus            = array_merge($currentStatus, $array);
        $job->extended_status = $newStatus;
        $job->save();

        return $job;
    }

    /**
     * @param ImportJob $job
     * @param string    $stage
     *
     * @return ImportJob
     */
    public function setStage(ImportJob $job, string $stage): ImportJob
    {
        $job->stage = $stage;
        $job->save();

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
        Log::debug(sprintf('Set status of job "%s" to "%s"', $job->key, $status));
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
     * @deprecated
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

    /**
     * @param ImportJob $job
     * @param array     $transactions
     *
     * @return ImportJob
     */
    public function setTransactions(ImportJob $job, array $transactions): ImportJob
    {
        $job->transactions = $transactions;
        $job->save();

        return $job;
    }

    /**
     * Add message to job.
     *
     * @param ImportJob $job
     * @param string    $error
     *
     * @return ImportJob
     */
    public function addErrorMessage(ImportJob $job, string $error): ImportJob
    {
        $errors      = $job->errors;
        $errors[]    = $error;
        $job->errors = $errors;
        $job->save();

        return $job;
    }

    /**
     * @param ImportJob $job
     * @param Tag       $tag
     *
     * @return ImportJob
     */
    public function setTag(ImportJob $job, Tag $tag): ImportJob
    {
        $job->tag()->associate($tag);
        $job->save();

        return $job;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    protected function validSize(UploadedFile $file): bool
    {
        $size = $file->getSize();

        return $size > $this->maxUploadSize;
    }


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
    public function storeFileUpload(ImportJob $job, string $name, UploadedFile $file): MessageBag
    {
        $messages = new MessageBag;
        if ($this->validSize($file)) {
            $name = e($file->getClientOriginalName());
            $messages->add('size', (string)trans('validation.file_too_large', ['name' => $name]));

            return $messages;
        }
        $count = $job->attachments()->get()->filter(
            function (Attachment $att) use($name) {
                return $att->filename === $name;
            }
        )->count();

        if ($count > 0) {
            // don't upload, but also don't complain about it.
            Log::error(sprintf('Detected duplicate upload. Will ignore second "%s" file.', $name));

            return new MessageBag;
        }

        $attachment = new Attachment; // create Attachment object.
        $attachment->user()->associate($job->user);
        $attachment->attachable()->associate($job);
        $attachment->md5      = md5_file($file->getRealPath());
        $attachment->filename = $name;
        $attachment->mime     = $file->getMimeType();
        $attachment->size     = $file->getSize();
        $attachment->uploaded = 0;
        $attachment->save();
        $fileObject = $file->openFile('r');
        $fileObject->rewind();
        $content   = $fileObject->fread($file->getSize());
        $encrypted = Crypt::encrypt($content);

        // store it:
        $this->uploadDisk->put($attachment->fileName(), $encrypted);
        $attachment->uploaded = 1; // update attachment
        $attachment->save();

        // return it.
        return new MessageBag;
    }
}
