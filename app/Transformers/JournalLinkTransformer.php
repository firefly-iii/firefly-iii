<?php
/**
 * JournalLinkTransformer.php
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


use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournalLink;
use League\Fractal\TransformerAbstract;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class JournalLinkTransformer
 */
class JournalLinkTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * CurrencyTransformer constructor.
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
     * @param TransactionJournalLink $link
     *
     * @return array
     */
    public function transform(TransactionJournalLink $link): array
    {
        $notes = null;
        /** @var Note $note */
        $note = $link->notes()->first();
        if (null !== $note) {
            $notes = $note->text;
        }

        $data = [
            'id'         => (int)$link->id,
            'created_at' => $link->created_at->toAtomString(),
            'updated_at' => $link->updated_at->toAtomString(),
            'inward_id'  => $link->source_id,
            'outward_id' => $link->destination_id,
            'notes'      => $notes,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/transaction_links/' . $link->id,
                ],
            ],
        ];

        return $data;
    }
}
