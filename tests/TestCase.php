<?php
use Carbon\Carbon;
use FireflyIII\Models\Preference;
use FireflyIII\User;

/**
 * Class TestCase
 */

/** @noinspection PhpUndefinedClassInspection */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * @param User   $user
     * @param string $range
     */
    public function changeDateRange(User $user, $range)
    {
        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y', 'custom'];
        if (in_array($range, $valid)) {
            Preference::where('user_id', $user->id)->where('name', 'viewRange')->delete();
            Preference::create(
                [
                    'user_id' => $user->id,
                    'name'    => 'viewRange',
                    'data'    => $range,
                ]
            );
            // set period to match?

        }
        // if selected "custom", change the session to a weird custom range:
        // (20 days):
        if ($range === "custom") {
            $this->session(
                [
                    'start' => Carbon::now(),
                    'end'   => Carbon::now()->subDays(20),
                ]
            );
        }
    }

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
     * @return array
     */
    public function dateRangeProvider()
    {
        return [
            'one day'      => ['1D'],
            'one week'     => ['1W'],
            'one month'    => ['1M'],
            'three months' => ['3M'],
            'six months'   => ['6M'],
            'one year'     => ['1Y'],
            'custom range' => ['custom'],
        ];
    }

    /**
     * @return User
     */
    public function emptyUser()
    {
        return User::find(2);
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


        $this->session(
            [
                'start' => Carbon::now()->startOfMonth(),
                'end'   => Carbon::now()->endOfMonth(),
                'first' => Carbon::now()->startOfYear(),
            ]
        );


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
     * @return User
     */
    public function toBeDeletedUser()
    {
        return User::find(3);
    }

    /**
     * @return User
     */
    public function user()
    {
        $user = User::find(1);

        return $user;
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
