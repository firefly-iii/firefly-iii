<?php

/**
 * LinkTypeSeeder.php
 * Copyright (c) 2019 james@firefly-iii.org.
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

namespace Database\Seeders;

use FireflyIII\Models\LinkType;
use Illuminate\Database\Seeder;
use PDOException;

/**
 * Class LinkTypeSeeder.
 */
class LinkTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'     => 'Related',
                'inward'   => 'relates to',
                'outward'  => 'relates to',
                'editable' => false,
            ],
            [
                'name'     => 'Refund',
                'inward'   => 'is (partially) refunded by',
                'outward'  => '(partially) refunds',
                'editable' => false,
            ],
            [
                'name'     => 'Paid',
                'inward'   => 'is (partially) paid for by',
                'outward'  => '(partially) pays for',
                'editable' => false,
            ],
            [
                'name'     => 'Reimbursement',
                'inward'   => 'is (partially) reimbursed by',
                'outward'  => '(partially) reimburses',
                'editable' => false,
            ],
        ];
        foreach ($types as $type) {
            if (null === LinkType::where('name', $type['name'])->first()) {
                try {
                    LinkType::create($type);
                } catch (PDOException $e) {
                    // @ignoreException
                }
            }
        }
    }
}
