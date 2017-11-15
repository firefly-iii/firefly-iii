<?php
/**
 * AttachmentRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
