<?php
/**
 * LinkTypeSeeder.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use FireflyIII\Models\LinkType;
use Illuminate\Database\Seeder;

/**
 * Class LinkTypeSeeder
 */
class LinkTypeSeeder extends Seeder
{
    /**
     *
     */
    public function run()
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
            try {
                LinkType::create($type);
            } catch (PDOException $e) {
                Log::info(sprintf('Could not create link type "%s". It might exist already.', $type['name']));
            }
        }
    }
}
