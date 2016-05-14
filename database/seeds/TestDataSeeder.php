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
use FireflyIII\Support\Migration\TestData;
use Illuminate\Database\Seeder;

/**
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $disk     = Storage::disk('database');
        $env      = App::environment();
        $fileName = 'seed.' . $env . '.json';
        if ($disk->exists($fileName)) {
            $file = json_decode($disk->get($fileName), true);
            // run the file:
            TestData::run($file);
        }
    }
}
