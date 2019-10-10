<?php
/**
 * ImportJobTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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


use FireflyIII\Models\ImportJob;
use Log;

/**
 * Class ImportJobTransformer
 */
class ImportJobTransformer extends AbstractTransformer
{
    /**
     * PiggyBankTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
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
        $tag    = $importJob->tag;
        $tagId  = null;
        $tagTag = null;
        if (null !== $tag) {
            $tagId  = $tag->id;
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
            'configuration'   => json_encode($importJob->configuration),
            'extended_status' => json_encode($importJob->extended_status),
            'transactions'    => json_encode($importJob->transactions),
            'errors'          => json_encode($importJob->errors),

            'links' => [
                [
                    'rel' => 'self',
                    'uri' => '/import/' . $importJob->key,
                ],
            ],
        ];

        return $data;
    }
}
