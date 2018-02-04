<?php
/**
 * BillTransformer.php
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

use FireflyIII\Models\Bill;
use League\Fractal\TransformerAbstract;

/**
 * Class BillTransformer
 */
class BillTransformer extends TransformerAbstract
{

    /**
     * @param Bill $bill
     *
     * @return array
     */
    public function transform(Bill $bill): array
    {
        return [
            'id'          => (int)$bill->id,
            'name'        => $bill->name,
            'match'       => explode(',', $bill->match),
            'amount_min'  => round($bill->amount_min, 2),
            'amount_max'  => round($bill->amount_max, 2),
            'date'        => $bill->date->format('Y-m-d'),
            'repeat_freq' => $bill->repeat_freq,
            'skip'        => (int)$bill->skip,
            'automatch'   => intval($bill->automatch) === 1,
            'active'      => intval($bill->active) === 1,
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/' . $bill->id,
                ],
            ],
        ];
    }
}