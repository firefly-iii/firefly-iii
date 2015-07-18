<?php

namespace FireflyIII\Helpers\Attachments;

use Auth;
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

    // move to config:
    protected $maxUploadSize = 1048576; // 1MB per file
    protected $allowedMimes  = ['image/png','image/jpeg','application/pdf'];

    public $errors;
    public $messages;

    /**
     *
     */
    public function __construct()
    {
        $this->errors   = new MessageBag;
        $this->messages = new MessageBag;
    }

    /**
     * @param Model $model
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model)
    {
        $files = Input::file('attachments');
        foreach ($files as $entry) {
            if (!is_null($entry)) {
                $this->processFile($entry, $model);
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
        $md5   = md5_file($file->getPath());
        $class = get_class($model);
        $count = Auth::user()->attachments()->where('md5', $md5)->where('attachable_id', $model->id)->where('attachable_type', $class)->count();

        return ($count > 0);
    }

    /**
     * @param UploadedFile $file
     */
    protected function processFile(UploadedFile $file, Model $model)
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

        // create Attachment object.
        $attachment = new Attachment;
        $attachment->user()->associate(Auth::user());
        $attachment->attachable()->associate($model);
        $attachment->md5      = md5_file($file->getPath());
        $attachment->filename = $file->getClientOriginalName();
        $attachment->mime     = $file->getMimeType();
        $attachment->size     = $file->getSize();
        $attachment->uploaded = 0;
        $attachment->save();

        // encrypt and move file to storage.
        $path      = $file->getRealPath();
        $content   = file_get_contents($path);
        $encrypted = Crypt::encrypt($content);

        // store it:
        $upload = storage_path('upload') . DIRECTORY_SEPARATOR . 'at-' . $attachment->id . '.data';
        if (is_writable(dirname($upload))) {
            file_put_contents($upload, $encrypted);
        }

        // update Attacment.
        $attachment->uploaded = 1;
        $attachment->save();

        // add message:
        $this->messages->add('attachments', 'File ' . e($file->getClientOriginalName()) . ' was uploaded successfully.');

        // return it.
        return $attachment;


    }

    protected function validMime(UploadedFile $file)
    {
        $mime = $file->getMimeType();
        $name = $file->getClientOriginalName();

        if (!in_array($mime, $this->allowedMimes)) {
            $err = 'File ' . e($name) . ' is of type ' . e($mime) . '.';
            $this->errors->add('attachments', $err);

            return false;
        }

        return true;
    }

    protected function validSize(UploadedFile $file)
    {
        $size = $file->getSize();
        $name = $file->getClientOriginalName();
        if ($size > $this->maxUploadSize) {

            $err = 'File ' . e($name) . ' is too large.';
            $this->errors->add('attachments', $err);

            return false;
        }

        return true;
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


}