<?php
declare(strict_types = 1);
/**
 * TestDataSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Carbon\Carbon;
use FireflyIII\Events\BudgetLimitStored;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Support\Migration\TestData;
use Illuminate\Database\Seeder;

/**
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    /** @var  Carbon */
    public $end;
    /** @var  Carbon */
    public $start;

    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {
        $this->start = Carbon::create()->subYears(2)->startOfYear();
        $this->end   = Carbon::now();

    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $current = clone $this->start;
        while ($current < $this->end) {
            $month = $current->format('F Y');

            $current->addMonth();
        }
    }
}
