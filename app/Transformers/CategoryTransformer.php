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
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;

/**
 * Class CategoryTransformer
 */
class CategoryTransformer extends AbstractTransformer
{
    private readonly TransactionCurrency $primaryCurrency;

    /**
     * CategoryTransformer constructor.
     */
    public function __construct()
    {
        $this->primaryCurrency = Amount::getPrimaryCurrency();
    }

    /**
     * Convert category.
     */
    public function transform(Category $category): array
    {

        return [
            'id'                              => $category->id,
            'created_at'                      => $category->created_at->toAtomString(),
            'updated_at'                      => $category->updated_at->toAtomString(),
            'name'                            => $category->name,
            'notes'                           => $category->meta['notes'],

            // category never has currency settings.
            'object_has_currency_setting'     => false,
            'primary_currency_id'             => (string)$this->primaryCurrency->id,
            'primary_currency_name'           => $this->primaryCurrency->name,
            'primary_currency_code'           => $this->primaryCurrency->code,
            'primary_currency_symbol'         => $this->primaryCurrency->symbol,
            'primary_currency_decimal_places' => (int)$this->primaryCurrency->decimal_places,
            'spent'                           => $this->beautify($category->meta['spent']),
            'pc_spent'                        => $this->beautify($category->meta['pc_spent']),
            'earned'                          => $this->beautify($category->meta['earned']),
            'pc_earned'                       => $this->beautify($category->meta['pc_earned']),
            'transferred'                     => $this->beautify($category->meta['transfers']),
            'pc_transferred'                  => $this->beautify($category->meta['pc_transfers']),
            'links'                           => [
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
            $data['sum'] = Steam::bcround($data['sum'], (int)$data['currency_decimal_places']);
            $return[]    = $data;
        }

        return $return;
    }
}
