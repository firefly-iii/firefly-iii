<?php

namespace FireflyIII\Repositories\Attachment;

use FireflyIII\Models\Attachment;

/**
 * Interface AttachmentRepositoryInterface
 *
 * @package FireflyIII\Repositories\Attachment
 */
interface AttachmentRepositoryInterface
{

    /**
     * @param Attachment $attachment
     * @param array      $attachmentData
     *
     * @return Attachment
     */
    public function update(Attachment $attachment, array $attachmentData);

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function destroy(Attachment $attachment);
}

