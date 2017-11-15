<?php
/**
 * AttachmentHelperInterface.php
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

namespace FireflyIII\Helpers\Attachments;

use FireflyIII\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Interface AttachmentHelperInterface.
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
     * @param null|array $files
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model, ?array $files): bool;
}
