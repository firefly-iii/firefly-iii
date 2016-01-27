<?php

namespace FireflyIII\Helpers\Attachments;

use FireflyIII\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

/**
 * Interface AttachmentHelperInterface
 *
 * @package FireflyIII\Helpers\Attachments
 */
interface AttachmentHelperInterface
{

    /**
     * @param Attachment $attachment
     *
     * @return mixed
     */
    public function getAttachmentLocation(Attachment $attachment);

    /**
     * @return MessageBag
     */
    public function getErrors();

    /**
     * @return MessageBag
     */
    public function getMessages();

    /**
     * @param Model $model
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model);

}
