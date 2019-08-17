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
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Log;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ImportJobRepository.
 *
 *
 */
class ImportJobRepository implements ImportJobRepositoryInterface
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    protected $uploadDisk;
    /** @var int */
    private $maxUploadSize;
    /** @var User */
    private $user;

    public function __construct()
    {
        $this->maxUploadSize = (int)config('firefly.maxUploadSize');
        $this->uploadDisk    = Storage::disk('upload');

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
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
     * Append transactions to array instead of replacing them.
     *
     * @param ImportJob $job
     * @param array     $transactions
     *
     * @return ImportJob
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function appendTransactions(ImportJob $job, array $transactions): ImportJob
    {
        Log::debug(sprintf('Now in appendTransactions(%s)', $job->key));
        $existingTransactions = $this->getTransactions($job);
        $new                  = array_merge($existingTransactions, $transactions);
        Log::debug(sprintf('Old transaction count: %d', count($existingTransactions)));
        Log::debug(sprintf('To be added transaction count: %d', count($transactions)));
        Log::debug(sprintf('New count: %d', count($new)));
        $this->setTransactions($job, $new);

        return $job;
    }

    /**
     * @param ImportJob $job
     *
     * @return int
     */
    public function countTransactions(ImportJob $job): int
    {
        $info = $job->transactions ?? [];
        if (isset($info['count'])) {
            return (int)$info['count'];
        }

        return 0;
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
            if (null === $existing) {
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
     * @param int $jobId
     *
     * @return ImportJob|null
     */
    public function find(int $jobId): ?ImportJob
    {
        return $this->user->importJobs()->find($jobId);
    }

    /**
     * @param string $key
     *
     * @return ImportJob|null
     */
    public function findByKey(string $key): ?ImportJob
    {
        /** @var ImportJob $result */
        $result = $this->user->importJobs()->where('key', $key)->first(['import_jobs.*']);
        if (null === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Return all import jobs.
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->user->importJobs()->get();
    }

    /**
     * Return all attachments for job.
     *
     * @param ImportJob $job
     *
     * @return Collection
     */
    public function getAttachments(ImportJob $job): Collection
    {
        return $job->attachments()->get();
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
        return $job->configuration;
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
     * Return transactions from attachment.
     *
     * @param ImportJob $job
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getTransactions(ImportJob $job): array
    {
        // this will overwrite all transactions currently in the job.
        $disk     = Storage::disk('upload');
        $filename = sprintf('%s-%s.crypt.json', $job->created_at->format('Ymd'), $job->key);
        $array    = [];
        if ($disk->exists($filename)) {
            $json  = Crypt::decrypt($disk->get($filename));
            $array = json_decode($json, true);
        }
        if (false === $array) {
            $array = [];
        }

        return $array;
    }

    /**
     * @param ImportJob $job
     * @param array     $configuration
     *
     * @return ImportJob
     */
    public function setConfiguration(ImportJob $job, array $configuration): ImportJob
    {
        Log::debug('Updating configuration...');
        //Log::debug(sprintf('Incoming config for job "%s" is: ', $job->key), $configuration);
        $currentConfig      = $job->configuration;
        $newConfig          = array_merge($currentConfig, $configuration);
        $job->configuration = $newConfig;
        $job->save();

        //Log::debug(sprintf('Set config of job "%s" to: ', $job->key), $newConfig);

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
     * @param ImportJob $job
     * @param array     $transactions
     *
     * @return ImportJob
     */
    public function setTransactions(ImportJob $job, array $transactions): ImportJob
    {
        // this will overwrite all transactions currently in the job.
        $disk     = Storage::disk('upload');
        $filename = sprintf('%s-%s.crypt.json', $job->created_at->format('Ymd'), $job->key);
        $json     = Crypt::encrypt(json_encode($transactions));

        // set count for easy access
        $array             = ['count' => count($transactions)];
        $job->transactions = $array;
        $job->save();
        // store file.
        $disk->put($filename, $json);

        return $job;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Handle upload for job.
     *
     * @param ImportJob $job
     * @param string    $name
     * @param string    $fileName
     *
     * @return MessageBag
     */
    public function storeCLIUpload(ImportJob $job, string $name, string $fileName): MessageBag
    {
        $messages = new MessageBag;
        if (!file_exists($fileName)) {
            $messages->add('notfound', sprintf('File not found: %s', $fileName));

            return $messages;
        }

        $count = $job->attachments()->get()->filter(
            function (Attachment $att) use ($name) {
                return $att->filename === $name;
            }
        )->count();

        if ($count > 0) {// don't upload, but also don't complain about it.
            Log::error(sprintf('Detected duplicate upload. Will ignore second "%s" file.', $name));

            return new MessageBag;
        }
        $content    = file_get_contents($fileName);
        $attachment = new Attachment; // create Attachment object.
        $attachment->user()->associate($job->user);
        $attachment->attachable()->associate($job);
        $attachment->md5      = md5($content);
        $attachment->filename = $name;
        $attachment->mime     = 'plain/txt';
        $attachment->size     = strlen($content);
        $attachment->uploaded = false;
        $attachment->save();
        $encrypted = Crypt::encrypt($content);

        $this->uploadDisk->put($attachment->fileName(), $encrypted);
        $attachment->uploaded = true; // update attachment
        $attachment->save();

        return new MessageBag;
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
            function (Attachment $att) use ($name) {
                return $att->filename === $name;
            }
        )->count();
        if ($count > 0) { // don't upload, but also don't complain about it.
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
        $attachment->uploaded = false;
        $attachment->save();
        $fileObject = $file->openFile();
        $fileObject->rewind();


        if (0 === $file->getSize()) {
            throw new FireflyException('Cannot upload empty or non-existent file.');
        }

        $content   = $fileObject->fread($file->getSize());
        $encrypted = Crypt::encrypt($content);
        $this->uploadDisk->put($attachment->fileName(), $encrypted);
        $attachment->uploaded = true; // update attachment
        $attachment->save();

        return new MessageBag;
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
     * @param ImportJob $job
     *
     * @return int
     */
    public function countByTag(ImportJob $job): int
    {
        return $job->tag->transactionJournals->count();
    }
}
