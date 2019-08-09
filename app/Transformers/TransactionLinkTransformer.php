<?php
/**
 * TransactionLinkTransformer.php
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


use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Log;

/**
 *
 * Class TransactionLinkTransformer
 */
class TransactionLinkTransformer extends AbstractTransformer
{
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     * Constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(JournalRepositoryInterface::class);

        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return array
     */
    public function transform(TransactionJournalLink $link): array
    {
        $notes = $this->repository->getLinkNoteText($link);
        $data  = [
            'id'         => (int)$link->id,
            'created_at' => $link->created_at->toAtomString(),
            'updated_at' => $link->updated_at->toAtomString(),
            'inward_id'  => $link->source_id,
            'outward_id' => $link->destination_id,
            'notes'      => '' === $notes ? null : $notes,
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
