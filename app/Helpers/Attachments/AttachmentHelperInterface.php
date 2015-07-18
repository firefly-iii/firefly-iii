<?php

namespace FireflyIII\Helpers\Attachments;

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
     * @param Model $model
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model);

    /**
     * @return MessageBag
     */
    public function getErrors();

    /**
     * @return MessageBag
     */
    public function getMessages();

}