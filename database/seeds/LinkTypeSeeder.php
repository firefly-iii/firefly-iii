<?php
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
