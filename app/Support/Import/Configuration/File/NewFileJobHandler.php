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
use FireflyIII\Console\Commands\Import;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\MessageBag;
use Log;
use Storage;
use Exception;

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
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getNextData(): array
    {
        $importFileTypes   = [];
        $defaultImportType = config('import.options.file.default_import_format');

        foreach (config('import.options.file.import_formats') as $type) {
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
        $messages    = new MessageBag;
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

        $this->repository->setStage($this->importJob, 'configure-upload');

        return new MessageBag();

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

    /**
     * @param Attachment $attachment
     *
     * @throws FireflyException
     */
    private function storeConfig(Attachment $attachment): void
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
}