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


use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class JournalMetaTransformer
 */
class JournalMetaTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['transactions'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

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
    }

    /**
     * Include any transactions.
     *
     * @param TransactionJournalMeta $meta
     *
     * @codeCoverageIgnore
     * @return FractalCollection
     */
    public function includeTransactions(TransactionJournalMeta $meta): FractalCollection
    {
        $journal  = $meta->transactionJournal;
        $pageSize = intval(app('preferences')->getForUser($journal->user, 'listPageSize', 50)->data);

        // journals always use collector and limited using URL parameters.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($journal->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withCategoryInformation();
        $collector->setAllAssetAccounts();
        $collector->setJournals(new Collection([$journal]));
        if (!is_null($this->parameters->get('start')) && !is_null($this->parameters->get('end'))) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $journals = $collector->getJournals();

        return $this->collection($journals, new TransactionTransformer($this->parameters), 'transactions');
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
            'updated_at' => $meta->updated_at->toAtomString(),
            'created_at' => $meta->created_at->toAtomString(),
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