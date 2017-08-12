<?php
/**
 * AttachmentRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Attachment;

use Carbon\Carbon;
use FireflyIII\Models\Attachment;
use FireflyIII\User;
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
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function exists(Attachment $attachment): bool;

    /**
     * @param int $id
     *
     * @return Attachment
     */
    public function find(int $id): Attachment;

    /**
     * @param int $id
     *
     * @return Attachment
     */
    public function findWithoutUser(int $id): Attachment;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBetween(Carbon $start, Carbon $end): Collection;

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getContent(Attachment $attachment): string;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param Attachment $attachment
     * @param array      $attachmentData
     *
     * @return Attachment
     */
    public function update(Attachment $attachment, array $attachmentData): Attachment;
}

