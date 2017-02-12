<?php

namespace Tests;

use FireflyIII\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Log;
use Mockery;
use FireflyIII\Models\Preference;
use Carbon\Carbon;
/**
 * Class TestCase
 *
 * @package Tests
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @return User
     */
    public function user()
    {
        $user = User::find(1);

        return $user;
    }

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
        if ($range === 'custom') {
            $this->session(
                [
                    'start' => Carbon::now()->subDays(20),
                    'end'   => Carbon::now(),
                ]
            );
        }
    }


    /**
     * @param string $class
     *
     * @return \Mockery\MockInterface
     */
    protected function mock($class)
    {
        Log::debug(sprintf('Will now mock %s', $class));
        $object = Mockery::mock($class);
        $this->app->instance($class, $object);

        return $object;
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

}
