<?php
/**
 * AttachmentHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Attachments;

use Crypt;
use FireflyIII\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Input;
use Log;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TypeError;

/**
 * Class AttachmentHelper
 *
 * @package FireflyIII\Helpers\Attachments
 */
class AttachmentHelper implements AttachmentHelperInterface
{

    /** @var MessageBag */
    public $errors;
    /** @var MessageBag */
    public $messages;
    /** @var array */
    protected $allowedMimes;
    /** @var int */
    protected $maxUploadSize;

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    protected $uploadDisk;

    /**
     *
     */
    public function __construct()
    {
        $this->maxUploadSize = config('firefly.maxUploadSize');
        $this->allowedMimes  = config('firefly.allowedMimes');
        $this->errors        = new MessageBag;
        $this->messages      = new MessageBag;
        $this->uploadDisk    = Storage::disk('upload');
    }

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentLocation(Attachment $attachment): string
    {
        $path = sprintf('%s%sat-%d.data', storage_path('upload'), DIRECTORY_SEPARATOR, $attachment->id);

        return $path;
    }

    /**
     * @return MessageBag
     */
    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    /**
     * @return MessageBag
     */
    public function getMessages(): MessageBag
    {
        return $this->messages;
    }

    /**
     * @param Model $model
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model): bool
    {
        $files = $this->getFiles();

        if (!is_null($files) && !is_array($files)) {
            $this->processFile($files, $model);
        }

        if (is_array($files)) {
            $this->processFiles($files, $model);
        }

        return true;
    }

    /**
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
        $count = auth()->user()->attachments()->where('md5', $md5)->where('attachable_id', $model->id)->where('attachable_type', $class)->count();

        if ($count > 0) {
            $msg = (string)trans('validation.file_already_attached', ['name' => $name]);
            $this->errors->add('attachments', $msg);

            return true;
        }

        return false;
    }

    /**
     *
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return Attachment
     */
    protected function processFile(UploadedFile $file, Model $model): Attachment
    {
        $validation = $this->validateUpload($file, $model);
        if ($validation === false) {
            return new Attachment;
        }

        $attachment = new Attachment; // create Attachment object.
        $attachment->user()->associate(auth()->user());
        $attachment->attachable()->associate($model);
        $attachment->md5      = md5_file($file->getRealPath());
        $attachment->filename = $file->getClientOriginalName();
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

        $name = e($file->getClientOriginalName()); // add message:
        $msg  = (string)trans('validation.file_attached', ['name' => $name]);
        $this->messages->add('attachments', $msg);

        // return it.
        return $attachment;


    }

    /**
     * @param UploadedFile $file
     *
     * @return bool
     */
    protected function validMime(UploadedFile $file): bool
    {
        $mime = e($file->getMimeType());
        $name = e($file->getClientOriginalName());

        if (!in_array($mime, $this->allowedMimes)) {
            $msg = (string)trans('validation.file_invalid_mime', ['name' => $name, 'mime' => $mime]);
            $this->errors->add('attachments', $msg);

            return false;
        }

        return true;
    }

    /**
     * @param UploadedFile $file
     *
     * @return bool
     */
    protected function validSize(UploadedFile $file): bool
    {
        $size = $file->getSize();
        $name = e($file->getClientOriginalName());
        if ($size > $this->maxUploadSize) {
            $msg = (string)trans('validation.file_too_large', ['name' => $name]);
            $this->errors->add('attachments', $msg);

            return false;
        }

        return true;
    }

    /**
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return bool
     */
    protected function validateUpload(UploadedFile $file, Model $model): bool
    {
        if (!$this->validMime($file)) {
            return false;
        }
        if (!$this->validSize($file)) {
            return false;
        }
        if ($this->hasFile($file, $model)) {
            return false;
        }

        return true;
    }

    /**
     * @return array|null|UploadedFile
     */
    private function getFiles()
    {
        $files = null;
        try {
            if (Input::hasFile('attachments')) {
                $files = Input::file('attachments');
            }
        } catch (TypeError $e) {
            // Log it, do nothing else.
            Log::error($e->getMessage());
        }

        return $files;
    }

    /**
     * @param array $files
     *
     * @param Model $model
     *
     * @return bool
     */
    private function processFiles(array $files, Model $model): bool
    {
        foreach ($files as $entry) {
            if (!is_null($entry)) {
                $this->processFile($entry, $model);
            }
        }

        return true;
    }


}
