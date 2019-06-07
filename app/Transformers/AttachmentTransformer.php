<?php
/**
 * AttachmentTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;


use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Log;

/**
 * Class AttachmentTransformer
 */
class AttachmentTransformer extends AbstractTransformer
{
    /** @var AttachmentRepositoryInterface */
    private $repository;

    /**
     * BillTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(AttachmentRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Transform attachment.
     *
     * @param Attachment $attachment
     *
     * @return array
     */
    public function transform(Attachment $attachment): array
    {
        $this->repository->setUser($attachment->user);

        return [
            'id'              => (int)$attachment->id,
            'created_at'      => $attachment->created_at->toAtomString(),
            'updated_at'      => $attachment->updated_at->toAtomString(),
            'attachable_id'   => $attachment->attachable_id,
            'attachable_type' => str_replace('FireflyIII\\Models\\', '', $attachment->attachable_type),
            'md5'             => $attachment->md5,
            'filename'        => $attachment->filename,
            'download_uri'    => route('api.v1.attachments.download', [$attachment->id]),
            'upload_uri'      => route('api.v1.attachments.upload', [$attachment->id]),
            'title'           => $attachment->title,
            'notes'           => $this->repository->getNoteText($attachment),
            'mime'            => $attachment->mime,
            'size'            => (int)$attachment->size,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/attachment/' . $attachment->id,
                ],
            ],
        ];
    }

}
