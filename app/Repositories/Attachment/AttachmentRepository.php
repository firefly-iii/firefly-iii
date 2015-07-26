<?php

namespace FireflyIII\Repositories\Attachment;

use FireflyIII\Models\Attachment;

/**
 * Class AttachmentRepository
 *
 * @package FireflyIII\Repositories\Attachment
 */
class AttachmentRepository implements AttachmentRepositoryInterface
{

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function destroy(Attachment $attachment)
    {
        /** @var \FireflyIII\Helpers\Attachments\AttachmentHelperInterface $helper */
        $helper = app('FireflyIII\Helpers\Attachments\AttachmentHelperInterface');

        $file = $helper->getAttachmentLocation($attachment);
        unlink($file);
        $attachment->delete();
    }

    /**
     * @param Attachment $attachment
     * @param array      $data
     *
     * @return Attachment
     */
    public function update(Attachment $attachment, array $data)
    {

        $attachment->title       = $data['title'];
        $attachment->description = $data['description'];
        $attachment->notes       = $data['notes'];
        $attachment->save();

        return $attachment;

    }
}
