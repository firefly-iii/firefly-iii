<?php
/**
 * AttachmentHelper.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Helpers\Attachments;

use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class AttachmentHelper.
 */
class AttachmentHelper implements AttachmentHelperInterface
{
    /** @var Collection All attachments */
    public $attachments;
    /** @var MessageBag All errors */
    public $errors;
    /** @var MessageBag All messages */
    public $messages;
    /** @var array Allowed mimes */
    protected $allowedMimes = [];
    /** @var int Max upload size. */
    protected $maxUploadSize = 0;

    /** @var \Illuminate\Contracts\Filesystem\Filesystem The disk where attachments are stored. */
    protected $uploadDisk;


    /**
     * AttachmentHelper constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->maxUploadSize = (int)config('firefly.maxUploadSize');
        $this->allowedMimes  = (array)config('firefly.allowedMimes');
        $this->errors        = new MessageBag;
        $this->messages      = new MessageBag;
        $this->attachments   = new Collection;
        $this->uploadDisk    = Storage::disk('upload');

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }


    /**
     * Returns the content of an attachment.
     *
     * @codeCoverageIgnore
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentContent(Attachment $attachment): string
    {
        $encryptedData = '';
        try {
            $encryptedData = $this->uploadDisk->get(sprintf('at-%d.data', $attachment->id));
        } catch (FileNotFoundException $e) {
            Log::error($e->getMessage());
        }
        try {
            $unencryptedData = Crypt::decrypt($encryptedData); // verified
        } catch (DecryptException|FileNotFoundException $e) {
            Log::error(sprintf('Could not decrypt data of attachment #%d: %s', $attachment->id, $e->getMessage()));
            $unencryptedData = $encryptedData;
        }

        return $unencryptedData;
    }

    /**
     * Returns the file path relative to upload disk for an attachment,
     *
     * @param Attachment $attachment
     * @codeCoverageIgnore
     * @return string
     */
    public function getAttachmentLocation(Attachment $attachment): string
    {
        return sprintf('%sat-%d.data', DIRECTORY_SEPARATOR, (int)$attachment->id);
    }

    /**
     * Get all attachments.
     * @codeCoverageIgnore
     * @return Collection
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /**
     * Get all errors.
     *
     * @return MessageBag
     * @codeCoverageIgnore
     */
    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    /**
     * Get all messages.
     *
     * @return MessageBag
     * @codeCoverageIgnore
     */
    public function getMessages(): MessageBag
    {
        return $this->messages;
    }

    /**
     * Uploads a file as a string.
     *
     * @param Attachment $attachment
     * @param string     $content
     *
     * @return bool
     */
    public function saveAttachmentFromApi(Attachment $attachment, string $content): bool
    {
        $resource = tmpfile();
        if (false === $resource) {
            // @codeCoverageIgnoreStart
            Log::error('Cannot create temp-file for file upload.');

            return false;
            // @codeCoverageIgnoreEnd
        }

        if ('' === $content) {
            Log::error('Cannot upload empty file.');

            return false;
        }

        $path = stream_get_meta_data($resource)['uri'];
        fwrite($resource, $content);
        $finfo       = finfo_open(FILEINFO_MIME_TYPE);
        $mime        = finfo_file($finfo, $path);
        $allowedMime = config('firefly.allowedMimes');
        if (!in_array($mime, $allowedMime, true)) {
            Log::error(sprintf('Mime type %s is not allowed for API file upload.', $mime));

            return false;
        }
        // is allowed? Save the file, without encryption.
        $this->uploadDisk->put($attachment->fileName(), $content);

        // update attachment.
        $attachment->md5      = md5_file($path);
        $attachment->mime     = $mime;
        $attachment->size     = strlen($content);
        $attachment->uploaded = true;
        $attachment->save();

        return true;
    }

    /**
     * Save attachments that get uploaded with models, through the app.
     *
     * @param object     $model
     * @param array|null $files
     *
     * @return bool
     * @throws FireflyException
     */
    public function saveAttachmentsForModel(object $model, ?array $files): bool
    {
        if (!($model instanceof Model)) {
            return false; // @codeCoverageIgnore
        }

        Log::debug(sprintf('Now in saveAttachmentsForModel for model %s', get_class($model)));
        if (is_array($files)) {
            Log::debug('$files is an array.');
            /** @var UploadedFile $entry */
            foreach ($files as $entry) {
                if (null !== $entry) {
                    $this->processFile($entry, $model);
                }
            }
            Log::debug('Done processing uploads.');
        }
        if (!is_array($files) || (is_array($files) && 0 === count($files))) {
            Log::debug('Array of files is not an array. Probably nothing uploaded. Will not store attachments.');
        }

        return true;
    }

    /**
     * Check if a model already has this file attached.
     *
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return bool
     */
    protected function hasFile(UploadedFile $file, Model $model): bool
    {
        $md5   = md5_file($file->getRealPath());
        $name  = $file->getClientOriginalName();
        $class = get_class($model);
        /** @noinspection PhpUndefinedFieldInspection */
        $count  = $model->user->attachments()->where('md5', $md5)->where('attachable_id', $model->id)->where('attachable_type', $class)->count();
        $result = false;
        if ($count > 0) {
            $msg = (string)trans('validation.file_already_attached', ['name' => $name]);
            $this->errors->add('attachments', $msg);
            Log::error($msg);
            $result = true;
        }

        return $result;
    }

    /**
     * Process the upload of a file.
     *
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return Attachment|null
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     * @throws FireflyException
     */
    protected function processFile(UploadedFile $file, Model $model): ?Attachment
    {
        Log::debug('Now in processFile()');
        $validation = $this->validateUpload($file, $model);
        $attachment = null;
        if (false !== $validation) {
            $attachment = new Attachment; // create Attachment object.
            /** @noinspection PhpUndefinedFieldInspection */
            $attachment->user()->associate($model->user);
            $attachment->attachable()->associate($model);
            $attachment->md5      = md5_file($file->getRealPath());
            $attachment->filename = $file->getClientOriginalName();
            $attachment->mime     = $file->getMimeType();
            $attachment->size     = $file->getSize();
            $attachment->uploaded = false;
            $attachment->save();
            Log::debug('Created attachment:', $attachment->toArray());

            $fileObject = $file->openFile();
            $fileObject->rewind();

            if (0 === $file->getSize()) {
                throw new FireflyException('Cannot upload empty or non-existent file.'); // @codeCoverageIgnore
            }

            $content   = $fileObject->fread($file->getSize());
            Log::debug(sprintf('Full file length is %d and upload size is %d.', strlen($content), $file->getSize()));

            // store it:
            $this->uploadDisk->put($attachment->fileName(), $content);
            $attachment->uploaded = true; // update attachment
            $attachment->save();
            $this->attachments->push($attachment);

            $name = e($file->getClientOriginalName()); // add message:
            $msg  = (string)trans('validation.file_attached', ['name' => $name]);
            $this->messages->add('attachments', $msg);
        }

        return $attachment;
    }

    /**
     * Verify if the mime of a file is valid.
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    protected function validMime(UploadedFile $file): bool
    {
        Log::debug('Now in validMime()');
        $mime = e($file->getMimeType());
        $name = e($file->getClientOriginalName());
        Log::debug(sprintf('Name is %s, and mime is %s', $name, $mime));
        Log::debug('Valid mimes are', $this->allowedMimes);
        $result = true;

        if (!in_array($mime, $this->allowedMimes, true)) {
            $msg = (string)trans('validation.file_invalid_mime', ['name' => $name, 'mime' => $mime]);
            $this->errors->add('attachments', $msg);
            Log::error($msg);

            $result = false;
        }

        return $result;
    }

    /**
     * Verify if the size of a file is valid.
     *
     * @codeCoverageIgnore
     *
     * @param UploadedFile $file
     *
     * @return bool
     */
    protected function validSize(UploadedFile $file): bool
    {
        $size   = $file->getSize();
        $name   = e($file->getClientOriginalName());
        $result = true;
        if ($size > $this->maxUploadSize) {
            $msg = (string)trans('validation.file_too_large', ['name' => $name]);
            $this->errors->add('attachments', $msg);
            Log::error($msg);

            $result = false;
        }

        return $result;
    }

    /**
     * Verify if the file was uploaded correctly.
     *
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return bool
     */
    protected function validateUpload(UploadedFile $file, Model $model): bool
    {
        Log::debug('Now in validateUpload()');
        $result = true;
        if (!$this->validMime($file)) {
            $result = false;
        }
        if (0 === $file->getSize()) {
            Log::error('Cannot upload empty file.');
            $result = false;
        }

        // @codeCoverageIgnoreStart
        // can't seem to reach this point.
        if (true === $result && !$this->validSize($file)) {
            $result = false;
        }
        // @codeCoverageIgnoreEnd
        if (true === $result && $this->hasFile($file, $model)) {
            $result = false;
        }

        return $result;
    }
}
