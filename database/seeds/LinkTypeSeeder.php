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
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
        $link           = new LinkType;
        $link->name     = 'Related';
        $link->inward   = 'relates to';
        $link->outward  = 'relates to';
        $link->editable = false;
        $link->save();

        $link           = new LinkType;
        $link->name     = 'Refund';
        $link->inward   = 'is (partially) refunded by';
        $link->outward  = '(partially) refunds';
        $link->editable = false;
        $link->save();

        $link           = new LinkType;
        $link->name     = 'Paid';
        $link->inward   = 'is (partially) paid for by';
        $link->outward  = '(partially) pays for';
        $link->editable = false;
        $link->save();

        $link           = new LinkType;
        $link->name     = 'Reimbursement';
        $link->inward   = 'is (partially) reimbursed by';
        $link->outward  = '(partially) reimburses';
        $link->editable = false;
        $link->save();
    }
}
