<?php

namespace FireflyIII\Helpers\Attachments;

use Auth;
use Config;
use Crypt;
use FireflyIII\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Input;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    /**
     *
     */
    public function __construct()
    {
        $this->maxUploadSize = Config::get('firefly.maxUploadSize');
        $this->allowedMimes  = Config::get('firefly.allowedMimes');
        $this->errors        = new MessageBag;
        $this->messages      = new MessageBag;
    }

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentLocation(Attachment $attachment)
    {
        $path = storage_path('upload') . DIRECTORY_SEPARATOR . 'at-' . $attachment->id . '.data';

        return $path;
    }

    /**
     * @return MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return MessageBag
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Model $model
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model)
    {
        $files = Input::file('attachments');

        if (is_array($files)) {
            foreach ($files as $entry) {
                if (!is_null($entry)) {
                    $this->processFile($entry, $model);
                }
            }
        } else {
            if (!is_null($files)) {
                $this->processFile($files, $model);
            }
        }

        return true;
    }

    /**
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return bool
     */
    protected function hasFile(UploadedFile $file, Model $model)
    {
        $md5   = md5_file($file->getRealPath());
        $name  = $file->getClientOriginalName();
        $class = get_class($model);
        $count = Auth::user()->attachments()->where('md5', $md5)->where('attachable_id', $model->id)->where('attachable_type', $class)->count();

        if ($count > 0) {
            $msg = (string)trans('validation.file_already_attached', ['name' => $name]);
            $this->errors->add('attachments', $msg);

            return true;
        }

        return false;
    }

    /**
     * @param UploadedFile $file
     * @param Model        $model
     *
     * @return bool|Attachment
     */
    protected function processFile(UploadedFile $file, Model $model)
    {
        $validation = $this->validateUpload($file, $model);
        if ($validation === false) {
            return false;
        }

        $attachment = new Attachment; // create Attachment object.
        $attachment->user()->associate(Auth::user());
        $attachment->attachable()->associate($model);
        $attachment->md5      = md5_file($file->getRealPath());
        $attachment->filename = $file->getClientOriginalName();
        $attachment->mime     = $file->getMimeType();
        $attachment->size     = $file->getSize();
        $attachment->uploaded = 0;
        $attachment->save();

        $path      = $file->getRealPath(); // encrypt and move file to storage.
        $content   = file_get_contents($path);
        $encrypted = Crypt::encrypt($content);

        // store it:
        $upload = $this->getAttachmentLocation($attachment);
        if (is_writable(dirname($upload))) {
            file_put_contents($upload, $encrypted);
        }

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
    protected function validMime(UploadedFile $file)
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
    protected function validSize(UploadedFile $file)
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
    protected function validateUpload(UploadedFile $file, Model $model)
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


}
