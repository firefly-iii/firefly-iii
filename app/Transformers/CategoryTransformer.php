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
    private readonly TransactionCurrency           $primary;
    private readonly OperationsRepositoryInterface $opsRepository;
    private readonly CategoryRepositoryInterface   $repository;

    /**
     * CategoryTransformer constructor.
     */
    public function __construct()
    {
        $this->opsRepository   = app(OperationsRepositoryInterface::class);
        $this->repository      = app(CategoryRepositoryInterface::class);
        $this->primary         = Amount::getPrimaryCurrency();
        $this->convertToNative = Amount::convertToPrimary();
    }

    /**
     * Convert category.
     */
    public function transform(Category $category): array
    {
        $this->opsRepository->setUser($category->user);
        $this->repository->setUser($category->user);

        $spent  = [];
        $earned = [];
        $start  = $this->parameters->get('start');
        $end    = $this->parameters->get('end');
        if (null !== $start && null !== $end) {
            $earned = $this->beautify($this->opsRepository->sumIncome($start, $end, null, new Collection([$category])));
            $spent  = $this->beautify($this->opsRepository->sumExpenses($start, $end, null, new Collection([$category])));
        }
        $primary = $this->primary;
        if (!$this->convertToNative) {
            $primary = null;
        }
        $notes = $this->repository->getNoteText($category);

        return [
            'id'                              => $category->id,
            'created_at'                      => $category->created_at->toAtomString(),
            'updated_at'                      => $category->updated_at->toAtomString(),
            'name'                            => $category->name,
            'notes'                           => $notes,
            'primary_currency_id'             => $primary instanceof TransactionCurrency ? (string)$primary->id : null,
            'primary_currency_code'           => $primary?->code,
            'primary_currency_symbol'         => $primary?->symbol,
            'primary_currency_decimal_places' => $primary?->decimal_places,
            'spent'                           => $spent,
            'earned'                          => $earned,
            'links'                           => [
                [
                    'rel' => 'self',
                    'uri' => '/categories/' . $category->id,
                ],
            ],
        ];
    }

    private function beautify(array $array): array
    {
        $return = [];
        foreach ($array as $data) {
            $data['sum'] = app('steam')->bcround($data['sum'], (int)$data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
