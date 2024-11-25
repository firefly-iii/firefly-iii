<?php

/**
 * AttachmentTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;

/**
 * Class AttachmentTransformer
 */
class AttachmentTransformer extends AbstractTransformer
{
    private AttachmentRepositoryInterface $repository;

    /**
     * BillTransformer constructor.
     */
    public function __construct()
    {
        $this->repository = app(AttachmentRepositoryInterface::class);
    }

    /**
     * Transform attachment.
     */
    public function transform(Attachment $attachment): array
    {
        $this->repository->setUser($attachment->user);

        return [
            'id'              => (string)$attachment->id,
            'created_at'      => $attachment->created_at->toAtomString(),
            'updated_at'      => $attachment->updated_at->toAtomString(),
            'attachable_id'   => (string)$attachment->attachable_id,
            'attachable_type' => str_replace('FireflyIII\Models\\', '', $attachment->attachable_type),
            'md5'             => $attachment->md5,
            'filename'        => $attachment->filename,
            'download_url'    => route('api.v1.attachments.download', [$attachment->id]),
            'upload_url'      => route('api.v1.attachments.upload', [$attachment->id]),
            'title'           => $attachment->title,
            'notes'           => $this->repository->getNoteText($attachment),
            'mime'            => $attachment->mime,
            'size'            => (int)$attachment->size,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/attachment/'.$attachment->id,
                ],
            ],
        ];
    }
}
