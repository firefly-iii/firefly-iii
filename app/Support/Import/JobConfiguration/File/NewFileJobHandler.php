<?php
/**
 * NewFileJobHandler.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */


declare(strict_types=1);

namespace FireflyIII\Support\Import\JobConfiguration\File;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class NewFileJobHandler
 */
class NewFileJobHandler implements FileConfigurationInterface
{
    /** @var AttachmentHelperInterface */
    private $attachments;
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
     *
     * Get the data necessary to show the configuration screen.
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function getNextData(): array
    {
        /** @var array $allowedTypes */
        $allowedTypes      = config('import.options.file.import_formats');
        $importFileTypes   = [];
        $defaultImportType = config('import.options.file.default_import_format');
        foreach ($allowedTypes as $type) {
            $importFileTypes[$type] = (string)trans('import.import_file_type_' . $type);
        }

        return [
            'default_type' => $defaultImportType,
            'file_types'   => $importFileTypes,
        ];
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob   = $importJob;
        $this->repository  = app(ImportJobRepositoryInterface::class);
        $this->attachments = app(AttachmentHelperInterface::class);
        $this->repository->setUser($importJob->user);
    }

    /**
     * Store config from job.
     *
     */
    public function storeConfiguration(): void
    {
        /** @var Collection $attachments */
        $attachments = $this->repository->getAttachments($this->importJob);
        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            // if file is configuration file, store it into the job.
            if ('configuration_file' === $attachment->filename) {
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
        $attachments = $this->repository->getAttachments($this->importJob);
        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {

            // check if content is UTF8:
            if (!$this->isUTF8($attachment)) {
                $message = (string)trans('import.file_not_utf8');
                Log::error($message);
                $messages->add('import_file', $message);
                // delete attachment:
                try {
                    $attachment->delete();
                    // @codeCoverageIgnoreStart
                } catch (Exception $e) {
                    throw new FireflyException(sprintf('Could not delete attachment: %s', $e->getMessage()));
                }

                // @codeCoverageIgnoreEnd

                return $messages;
            }

            // if file is configuration file, store it into the job.
            if ('configuration_file' === $attachment->filename) {
                $this->storeConfig($attachment);
            }
        }

        return $messages;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    private function isUTF8(Attachment $attachment): bool
    {
        $content = $this->attachments->getAttachmentContent($attachment);
        $result  = mb_detect_encoding($content, 'UTF-8', true);
        if (false === $result) {
            return false;
        }
        if ('ASCII' !== $result && 'UTF-8' !== $result) {
            return false; // @codeCoverageIgnore
        }

        return true;
    }

    /**
     * Take attachment, extract config, and put in job.\
     *
     * @param Attachment $attachment
     *
     */
    private function storeConfig(Attachment $attachment): void
    {
        $content = $this->attachments->getAttachmentContent($attachment);
        $json    = json_decode($content, true);
        if (null !== $json) {
            $this->repository->setConfiguration($this->importJob, $json);
        }
    }
}
