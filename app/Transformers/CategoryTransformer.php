<?php
/**
 * CategoryTransformer.php
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


use FireflyIII\Models\Category;
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

    /**
     * CategoryTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
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
        $this->opsRepository->setUser($category->user);

        $spent  = [];
        $earned = [];
        $start  = $this->parameters->get('start');
        $end    = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $earned = $this->beautify($this->opsRepository->sumIncome($start, $end, null, new Collection([$category])));
            $spent  = $this->beautify($this->opsRepository->sumExpenses($start, $end, null, new Collection([$category])));
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

    /**
     * @param array $array
     *
     * @return array
     */
    private function beautify(array $array): array
    {
        $return = [];
        foreach ($array as $data) {
            $data['sum'] = round($data['sum'], (int)$data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
