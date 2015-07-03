<?php

use Carbon\Carbon;
use FireflyIII\Models\AccountMeta;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartAccountControllerTest
 */
class ChartAccountControllerTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
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
     * @covers FireflyIII\Http\Controllers\Chart\AccountController::all
     */
    public function testAll()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset                = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $one                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $two                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $one->account_type_id = $asset->id;
        $two->account_type_id = $asset->id;
        $one->save();
        $two->save();
        $accounts = new Collection([$one, $two]);
        $date     = new Carbon;
        $this->be($user);

        // make one shared:
        AccountMeta::create(
            [
                'account_id' => $one->id,
                'name'       => 'accountRole',
                'data'       => 'sharedAsset'
            ]
        );


        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // fake!
        $repository->shouldReceive('getAccounts')->once()->andReturn($accounts);

        $this->call('GET', '/chart/account/month/' . $date->format('Y/m'));
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\AccountController::all
     */
    public function testAllShared()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $account  = FactoryMuffin::create('FireflyIII\Models\Account');
        $accounts = new Collection([$account]);
        $date     = new Carbon;
        $this->be($user);

        // make it shared:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'accountRole',
                'data'       => 'sharedAsset'
            ]
        );


        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // fake!
        $repository->shouldReceive('getAccounts')->once()->andReturn($accounts);


        $this->call('GET', '/chart/account/month/' . $date->format('Y/m') . '/shared');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\AccountController::frontpage
     */
    public function testFrontpage()
    {
        $accounts = new Collection([FactoryMuffin::create('FireflyIII\Models\Account')]);
        $user     = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // fake!
        $repository->shouldReceive('getFrontpageAccounts')->andReturn($accounts);

        $this->call('GET', '/chart/account/frontpage');
        $this->assertResponseOk();

    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\AccountController::single
     */
    public function testSingle()
    {
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        $this->be($account->user);

        $this->call('GET', '/chart/account/' . $account->id);
        $this->assertResponseOk();

    }
}
