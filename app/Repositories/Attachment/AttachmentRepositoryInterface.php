<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Attachment;

use FireflyIII\Models\Attachment;
use Illuminate\Support\Collection;

/**
 * Interface AttachmentRepositoryInterface
 *
 * @package FireflyIII\Repositories\Attachment
 */
interface AttachmentRepositoryInterface
{

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function destroy(Attachment $attachment): bool;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param Attachment $attachment
     * @param array      $attachmentData
     *
     * @return Attachment
     */
    public function update(Attachment $attachment, array $attachmentData): Attachment;
}

