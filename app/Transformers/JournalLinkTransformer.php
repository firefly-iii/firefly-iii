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


use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournalLink;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class JournalLinkTransformer
 */
class JournalLinkTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['inward', 'outward', 'link_type'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['inward', 'outward', 'link_type'];

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
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return Item
     */
    public function includeInward(TransactionJournalLink $link): Item
    {
        // need to use the collector to get the transaction :(
        // journals always use collector and limited using URL parameters.
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($link->source->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setJournals(new Collection([$link->source]));
        $transactions = $collector->getTransactions();

        return $this->item($transactions->first(), new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return Item
     */
    public function includeLinkType(TransactionJournalLink $link): Item
    {
        return $this->item($link->linkType, new LinkTypeTransformer($this->parameters), 'link_types');
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return Item
     */
    public function includeOutward(TransactionJournalLink $link): Item
    {
        // need to use the collector to get the transaction :(
        // journals always use collector and limited using URL parameters.
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($link->source->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setJournals(new Collection([$link->destination]));
        $transactions = $collector->getTransactions();

        return $this->item($transactions->first(), new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return array
     */
    public function transform(TransactionJournalLink $link): array
    {
        $notes = '';
        /** @var Note $note */
        $note = $link->notes()->first();
        if (null !== $note) {
            $notes = $note->text;
        }

        $data = [
            'id'         => (int)$link->id,
            'updated_at' => $link->updated_at->toAtomString(),
            'created_at' => $link->created_at->toAtomString(),
            'notes'      => $notes,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/journal_links/' . $link->id,
                ],
            ],
        ];

        return $data;
    }
}
