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
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AttachmentTransformer
 */
class AttachmentTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['user'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['user'];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * BillTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Attach the user.
     *
     * @codeCoverageIgnore
     *
     * @param Attachment $attachment
     *
     * @return Item
     */
    public function includeUser(Attachment $attachment): Item
    {
        return $this->item($attachment->user, new UserTransformer($this->parameters), 'user');
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
        return [
            'id'              => (int)$attachment->id,
            'updated_at'      => $attachment->updated_at->toAtomString(),
            'created_at'      => $attachment->created_at->toAtomString(),
            'attachable_type' => $attachment->attachable_type,
            'md5'             => $attachment->md5,
            'filename'        => $attachment->filename,
            'title'           => $attachment->title,
            'description'     => $attachment->description,
            'notes'           => $attachment->notes,
            'mime'            => $attachment->mime,
            'size'            => $attachment->size,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/attachment/' . $attachment->id,
                ],
            ],
        ];
    }

}