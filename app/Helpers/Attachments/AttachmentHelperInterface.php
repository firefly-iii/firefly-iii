<?php
/**
 * AttachmentHelperInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Attachments;

use FireflyIII\Models\Attachment;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Interface AttachmentHelperInterface.
 */
interface AttachmentHelperInterface
{
    /**
     * Get content of an attachment.
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentContent(Attachment $attachment): string;

    /**
     * Get the location of an attachment.
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentLocation(Attachment $attachment): string;

    /**
     * Get all attachments.
     *
     * @return Collection
     */
    public function getAttachments(): Collection;

    /**
     * Get all errors.
     *
     * @return MessageBag
     */
    public function getErrors(): MessageBag;

    /**
     * Get all messages/
     *
     * @return MessageBag
     */
    public function getMessages(): MessageBag;

    /**
     * Uploads a file as a string.
     *
     * @param Attachment $attachment
     * @param string     $content
     *
     * @return bool
     */
    public function saveAttachmentFromApi(Attachment $attachment, string $content): bool;

    /**
     * Save attachments that got uploaded.
     *
     * @param object     $model
     * @param null|array $files
     *
     * @return bool
     */
    public function saveAttachmentsForModel(object $model, ?array $files): bool;
}
