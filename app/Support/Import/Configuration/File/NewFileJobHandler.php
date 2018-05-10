<?php
/**
 * NewFileJobHandler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Import\Configuration\File;

use Crypt;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Storage;

/**
 * Class NewFileJobHandler
 *
 * @package FireflyIII\Support\Import\Configuration\File
 */
class NewFileJobHandler implements ConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Store data associated with current stage.
     *
     * @param array $data
     *
     * @throws FireflyException
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        // nothing to store, validate upload
        // and push to next stage.
        $messages = $this->validateAttachments();

        if ($messages->count() > 0) {
            return $messages;
        }

        // store config if it's in one of the attachments.
        $this->storeConfiguration();

        // set file type in config:
        $config              = $this->repository->getConfiguration($this->importJob);
        $config['file-type'] = $data['import_file_type'];
        $this->repository->setConfiguration($this->importJob, $config);
        $this->repository->setStage($this->importJob, 'configure-upload');

        return new MessageBag();

    }

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getNextData(): array
    {
        /** @var array $allowedTypes */
        $allowedTypes      = config('import.options.file.import_formats');
        $importFileTypes   = [];
        $defaultImportType = config('import.options.file.default_import_format');
        foreach ($allowedTypes as $type) {
            $importFileTypes[$type] = trans('import.import_file_type_' . $type);
        }

        return [
            'default_type' => $defaultImportType,
            'file_types'   => $importFileTypes,
        ];
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->importJob  = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);

    }

    /**
     * Take attachment, extract config, and put in job.\
     *
     * @param Attachment $attachment
     *
     * @throws FireflyException
     */
    public function storeConfig(Attachment $attachment): void
    {
        $disk = Storage::disk('upload');
        try {
            $content = $disk->get(sprintf('at-%d.data', $attachment->id));
            $content = Crypt::decrypt($content);
        } catch (FileNotFoundException $e) {
            Log::error($e->getMessage());
            throw new FireflyException($e->getMessage());
        }
        $json = json_decode($content, true);
        if (null !== $json) {
            $this->repository->setConfiguration($this->importJob, $json);
        }
    }

    /**
     * Store config from job.
     *
     * @throws FireflyException
     */
    public function storeConfiguration(): void
    {
        /** @var Collection $attachments */
        $attachments = $this->importJob->attachments;
        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            // if file is configuration file, store it into the job.
            if ($attachment->filename === 'configuration_file') {
                $this->storeConfig($attachment);
            }
        }
    }

    /**
     * Check if all attachments are UTF8.
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function validateAttachments(): MessageBag
    {
        $messages = new MessageBag;
        /** @var Collection $attachments */
        $attachments = $this->importJob->attachments;
        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {

            // check if content is UTF8:
            if (!$this->isUTF8($attachment)) {
                $message = trans('import.file_not_utf8');
                Log::error($message);
                $messages->add('import_file', $message);
                // delete attachment:
                try {
                    $attachment->delete();
                } catch (Exception $e) {
                    throw new FireflyException(sprintf('Could not delete attachment: %s', $e->getMessage()));
                }

                return $messages;
            }

            // if file is configuration file, store it into the job.
            if ($attachment->filename === 'configuration_file') {
                $this->storeConfig($attachment);
            }
        }

        return $messages;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     * @throws FireflyException
     */
    private function isUTF8(Attachment $attachment): bool
    {
        $disk = Storage::disk('upload');
        try {
            $content = $disk->get(sprintf('at-%d.data', $attachment->id));
            $content = Crypt::decrypt($content);
        } catch (FileNotFoundException|DecryptException $e) {
            Log::error($e->getMessage());
            throw new FireflyException($e->getMessage());
        }

        $result = mb_detect_encoding($content, 'UTF-8', true);
        if ($result === false) {
            return false;
        }
        if ($result !== 'ASCII' && $result !== 'UTF-8') {
            return false;
        }

        return true;
    }
}