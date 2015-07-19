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