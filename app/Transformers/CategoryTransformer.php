<?php
/**
 * CategoryTransformer.php
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
use FireflyIII\Models\Category;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class CategoryTransformer
 */
class CategoryTransformer extends TransformerAbstract
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
     * CategoryTransformer constructor.
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
     * @param Category $category
     *
     * @codeCoverageIgnore
     * @return FractalCollection
     */
    public function includeTransactions(Category $category): FractalCollection
    {
        $pageSize = intval(app('preferences')->getForUser($category->user, 'listPageSize', 50)->data);

        // journals always use collector and limited using URL parameters.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($category->user);
        $collector->withOpposingAccount()->withCategoryInformation()->withCategoryInformation();
        $collector->setAllAssetAccounts();
        $collector->setCategories(new Collection([$category]));
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
     * @param Category $category
     *
     * @codeCoverageIgnore
     * @return Item
     */
    public function includeUser(Category $category): Item
    {
        return $this->item($category->user, new UserTransformer($this->parameters), 'users');
    }

    /**
     * Convert category.
     *
     * @param Category $category
     *
     * @return array
     */
    public function transform(Category $category): array
    {
        $data = [
            'id'         => (int)$category->id,
            'updated_at' => $category->updated_at->toAtomString(),
            'created_at' => $category->created_at->toAtomString(),
            'name'       => $category->name,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/categories/' . $category->id,
                ],
            ],
        ];

        return $data;
    }

}