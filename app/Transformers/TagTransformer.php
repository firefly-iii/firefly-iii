<?php
/**
 * TagTransformer.php
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
use FireflyIII\Models\Tag;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class TagTransformer
 */
class TagTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['user', 'transactions'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * TagTransformer constructor.
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
     * @param Tag $tag
     *
     * @codeCoverageIgnore
     * @return FractalCollection
     */
    public function includeTransactions(Tag $tag): FractalCollection
    {
        $pageSize = intval(app('preferences')->getForUser($tag->user, 'listPageSize', 50)->data);

        // journals always use collector and limited using URL parameters.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($tag->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withCategoryInformation();
        $collector->setAllAssetAccounts();
        $collector->setTag($tag);
        if (!is_null($this->parameters->get('start')) && !is_null($this->parameters->get('end'))) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $journals = $collector->getJournals();

        return $this->collection($journals, new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * Include the user.
     *
     * @param Tag $tag
     *
     * @codeCoverageIgnore
     * @return Item
     */
    public function includeUser(Tag $tag): Item
    {
        return $this->item($tag->user, new UserTransformer($this->parameters), 'users');
    }

    /**
     * Transform a tag.
     *
     * @param Tag $tag
     *
     * @return array
     */
    public function transform(Tag $tag): array
    {
        $date = is_null($tag->date) ? null : $tag->date->format('Y-m-d');
        $data = [
            'id'          => (int)$tag->id,
            'updated_at'  => $tag->updated_at->toAtomString(),
            'created_at'  => $tag->created_at->toAtomString(),
            'tag'         => $tag->tag,
            'tag_mode'    => $tag->tagMode,
            'date'        => $date,
            'description' => $tag->description === '' ? null : $tag->description,
            'latitude'    => (float)$tag->latitude,
            'longitude'   => (float)$tag->longitude,
            'zoom_level'  => (int)$tag->zoomLevel,
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => '/tags/' . $tag->id,
                ],
            ],
        ];

        return $data;
    }

}