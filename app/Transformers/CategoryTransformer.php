<?php

/**
 * CategoryTransformer.php
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

use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Support\Collection;

/**
 * Class CategoryTransformer
 */
class CategoryTransformer extends AbstractTransformer
{
    private readonly bool                          $convertToNative;
    private readonly TransactionCurrency           $default;
    private readonly OperationsRepositoryInterface $opsRepository;
    private readonly CategoryRepositoryInterface   $repository;

    /**
     * CategoryTransformer constructor.
     */
    public function __construct()
    {
        $this->opsRepository   = app(OperationsRepositoryInterface::class);
        $this->repository      = app(CategoryRepositoryInterface::class);
        $this->default         = Amount::getNativeCurrency();
        $this->convertToNative = Amount::convertToNative();
    }

    /**
     * Convert category.
     */
    public function transform(Category $category): array
    {
        $this->opsRepository->setUser($category->user);
        $this->repository->setUser($category->user);

        $spent   = [];
        $earned  = [];
        $start   = $this->parameters->get('start');
        $end     = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $earned = $this->beautify($this->opsRepository->sumIncome($start, $end, null, new Collection([$category])));
            $spent  = $this->beautify($this->opsRepository->sumExpenses($start, $end, null, new Collection([$category])));
        }
        $default = $this->default;
        if (!$this->convertToNative) {
            $default = null;
        }
        $notes   = $this->repository->getNoteText($category);

        return [
            'id'                             => $category->id,
            'created_at'                     => $category->created_at->toAtomString(),
            'updated_at'                     => $category->updated_at->toAtomString(),
            'name'                           => $category->name,
            'notes'                          => $notes,
            'native_currency_id'             => null === $default ? null : (string) $default->id,
            'native_currency_code'           => $default?->code,
            'native_currency_symbol'         => $default?->symbol,
            'native_currency_decimal_places' => $default?->decimal_places,
            'spent'                          => $spent,
            'earned'                         => $earned,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => '/categories/'.$category->id,
                ],
            ],
        ];
    }

    private function beautify(array $array): array
    {
        $return = [];
        foreach ($array as $data) {
            $data['sum'] = app('steam')->bcround($data['sum'], (int) $data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
