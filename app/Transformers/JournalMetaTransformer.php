<?php
/**
 * JournalMetaTransformer.php
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


use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\TransformerAbstract;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class JournalMetaTransformer
 */
class JournalMetaTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * JournalMetaTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * Convert meta object.
     *
     * @param TransactionJournalMeta $meta
     *
     * @return array
     */
    public function transform(TransactionJournalMeta $meta): array
    {
        $data = [
            'id'         => (int)$meta->id,
            'created_at' => $meta->created_at->toAtomString(),
            'updated_at' => $meta->updated_at->toAtomString(),
            'name'       => $meta->name,
            'data'       => $meta->data,
            'hash'       => $meta->hash,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/journal_meta/' . $meta->id,
                ],
            ],
        ];

        return $data;
    }

}
