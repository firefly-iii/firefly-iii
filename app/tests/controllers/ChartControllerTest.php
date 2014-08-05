<?php

use Carbon\Carbon;
use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class ChartControllerTest
 */
class ChartControllerTest extends TestCase
{
    protected $_user;
//    protected $_repository;
    protected $_accounts;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
//        $this->_category = $this->mock('Firefly\Helper\Controllers\CategoryInterface');
        $this->_user = m::mock('User', 'Eloquent');

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCategoryShowChart()
    {
        $this->session(['start' => new Carbon, 'end' => new Carbon, 'range' => '1M']);
        $category = f::create('Category');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);


        $this->action('GET', 'ChartController@categoryShowChart', $category->id);
        $this->assertResponseOk();
    }

    public function testHomeAccount()
    {
        $account = f::create('Account');
        $this->session(['start' => new Carbon, 'end' => new Carbon, 'range' => '1M']);

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(1);
        $this->_accounts->shouldReceive('getByIds')->andReturn([$account]);


        $this->action('GET', 'ChartController@homeAccount');
        $this->assertResponseOk();
    }
}