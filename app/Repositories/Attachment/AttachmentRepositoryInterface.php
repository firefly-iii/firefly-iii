<?php
/**
 * AttachmentRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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

