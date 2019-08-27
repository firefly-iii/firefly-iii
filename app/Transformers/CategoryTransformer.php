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


use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CategoryTransformer
 */
class CategoryTransformer extends AbstractTransformer
{
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var CategoryRepositoryInterface */
    private $repository;

    /**
     * CategoryTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository    = app(CategoryRepositoryInterface::class);
        $this->opsRepository = app(OperationsRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
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
        $this->repository->setUser($category->user);
        $this->opsRepository->setUser($category->user);

        $spent  = [];
        $earned = [];
        $start  = $this->parameters->get('start');
        $end    = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $earned = array_values($this->opsRepository->earnedInPeriod($category, new Collection, $start, $end));
            $spent  = array_values($this->opsRepository->spentInPeriod($category, new Collection, $start, $end));
        }
        $data = [
            'id'         => (int)$category->id,
            'created_at' => $category->created_at->toAtomString(),
            'updated_at' => $category->updated_at->toAtomString(),
            'name'       => $category->name,
            'spent'      => $spent,
            'earned'     => $earned,
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
