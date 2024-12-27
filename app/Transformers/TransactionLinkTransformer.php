<?php

/**
 * TransactionLinkTransformer.php
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

use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;

/**
 * Class TransactionLinkTransformer
 */
class TransactionLinkTransformer extends AbstractTransformer
{
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->repository = app(JournalRepositoryInterface::class);
    }

    public function transform(TransactionJournalLink $link): array
    {
        $notes = $this->repository->getLinkNoteText($link);

        return [
            'id'           => (string) $link->id,
            'created_at'   => $link->created_at->toAtomString(),
            'updated_at'   => $link->updated_at->toAtomString(),
            'inward_id'    => (string) $link->source_id,
            'outward_id'   => (string) $link->destination_id,
            'link_type_id' => (string) $link->link_type_id,
            'notes'        => '' === $notes ? null : $notes,
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/transaction_links/'.$link->id,
                ],
            ],
        ];
    }
}
