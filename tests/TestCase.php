<?php
use Carbon\Carbon;
use FireflyIII\Models\Preference;
use FireflyIII\User;

/**
 * Class TestCase
 */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';


    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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
        $copy     = storage_path('database') . '/testing-copy.db';
        $original = storage_path('database') . '/testing.db';

        // move .env file over?
        if (!file_exists($copy)) {

            // maybe original does?
            if (!file_exists($original)) {
                touch($original);
                Artisan::call('migrate', ['--seed' => true]);
            }

            copy($original, $copy);
        } else {
            if (file_exists($copy)) {
                copy($copy, $original);
            }
        }
        // if the database copy does exists, copy back as original.

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }


    /**
     * @param string $class
     *
     * @return \Mockery\MockInterface
     */
    protected function mock($class)
    {
        $object = Mockery::mock($class);


        $this->app->instance($class, $object);

        return $object;
    }


}
