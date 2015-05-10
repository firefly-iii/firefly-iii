<?php

use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class TestCase
 */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        return $app;
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        // if the database copy does not exist, call migrate.
        $copy     = __DIR__ . '/../storage/database/testing-copy.db';
        $original = __DIR__ . '/../storage/database/testing.db';

        FactoryMuffin::loadFactories(__DIR__ . '/factories');

        if (!file_exists($copy)) {
            Log::debug('Created new database.');
            touch($original);
            Artisan::call('migrate');


            // create EUR currency
            /** @var TransactionCurrency $currency */
            $currency       = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
            $currency->code = 'EUR';
            $currency->save();
            Log::debug('Created new EUR currency.');
            copy($original, $copy);
        } else {

            if (file_exists($copy)) {
                Log::debug('Copied copy back over original.');
                copy($copy, $original);
            }
        }
        // if the database copy does exists, copy back as original.

        $this->session(
            [
                'start' => Carbon::now()->startOfMonth(),
                'end'   => Carbon::now()->endOfMonth(),
                'first' => Carbon::now()->startOfYear()
            ]
        );


    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();

        // delete copy original.
        //$original = __DIR__.'/../storage/database/testing.db';
        //unlink($original);

    }

    /**
     * @param string $class
     *
     * @return Mockery\MockInterface
     */
    public function mock($class)
    {
        $mock = Mockery::mock($class);

        $this->app->instance($class, $mock);

        return $mock;
    }


}
