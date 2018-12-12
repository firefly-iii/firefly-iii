<?php
/**
 * ImportJobTransformer.php
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


use FireflyIII\Models\ImportJob;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ImportJobTransformer
 */
class ImportJobTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * PiggyBankTransformer constructor.
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
     * Transform the import job.
     *
     * @param ImportJob $importJob
     *
     * @return array
     */
    public function transform(ImportJob $importJob): array
    {
        $tag= $importJob->tag;
        $tagId = null;
        $tagTag = null;
        if(null !== $tag) {
            $tagId = $tag->id;
            $tagTag = $tag->tag;
        }
        $data = [
            'id'              => (int)$importJob->id,
            'created_at'      => $importJob->created_at->toAtomString(),
            'updated_at'      => $importJob->updated_at->toAtomString(),
            'tag_id'          => $tagId,
            'tag_tag'         => $tagTag,
            'key'             => $importJob->key,
            'file_type'       => $importJob->file_type,
            'provider'        => $importJob->provider,
            'status'          => $importJob->status,
            'stage'           => $importJob->stage,
            'configuration'   => $importJob->configuration,
            'extended_status' => $importJob->extended_status,
            'transactions'    => $importJob->transactions,
            'errors'          => $importJob->errors,

            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/import/' . $importJob->key,
                ],
            ],
        ];

        return $data;
    }
}
