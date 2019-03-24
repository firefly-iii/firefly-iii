<?php
/**
 * TransactionGroupTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

/**
 * Class TransactionGroupTransformer
 */
class TransactionGroupTransformer extends AbstractTransformer
{
    /**
     * Constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            app('log')->warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param array $group
     *
     * @return array
     */
    public function transform(array $group): array
    {

        $first =reset($group['transactions']);
        $data = [
            'id'         => (int)$first['transaction_group_id'],
            'created_at' => $first['created_at']->toAtomString(),
            'updated_at' => $first['updated_at']->toAtomString(),
            'some_field' => 'some_value',
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/transactions/' . $first['transaction_group_id'],
                ],
            ],
        ];

        // do something else.

        return $data;
    }
}