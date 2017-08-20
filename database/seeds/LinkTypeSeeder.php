<?php
/**
 * LinkTypeSeeder.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
        $link->outward  = 'is related to';
        $link->editable = false;
        $link->save();

        $link           = new LinkType;
        $link->name     = 'Refund';
        $link->inward   = 'refunds';
        $link->outward  = 'is refunded by';
        $link->editable = false;
        $link->save();

        $link           = new LinkType;
        $link->name     = 'PartialRefund';
        $link->inward   = 'partially refunds';
        $link->outward  = 'is partially refunded by';
        $link->editable = false;
        $link->save();

        $link           = new LinkType;
        $link->name     = 'Paid';
        $link->inward   = 'pays for';
        $link->outward  = 'is paid for by';
        $link->editable = false;
        $link->save();

    }

}
