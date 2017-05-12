<?php
/**
 * AttachmentHelperInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Attachments;

use FireflyIII\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
     * @return string
     */
    public function getAttachmentLocation(Attachment $attachment): string;

    /**
     * @return Collection
     */
    public function getAttachments(): Collection;

    /**
     * @return MessageBag
     */
    public function getErrors(): MessageBag;

    /**
     * @return MessageBag
     */
    public function getMessages(): MessageBag;

    /**
     * @param Model      $model
     *
     * @param null|array $files
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model, array $files = null): bool;

}
