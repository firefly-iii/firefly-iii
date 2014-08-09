<?php


use Mockery as m;

/**
 * Class PreferencesControllerTest
 */
class PreferencesControllerTest extends TestCase
{

    protected $_user;
    protected $_helper;
    protected $_accounts;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_helper = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');

    }

    public function tearDown()
    {
        m::close();
    }

    public function testIndex()
    {
        $viewRange = $this->mock('Preference');
        $viewRange->shouldReceive('getAttribute')->with('data')->andReturn('1M');

        $this->_accounts->shouldReceive('getDefault')->andReturn([]);
        $this->_helper->shouldReceive('get')->with('viewRange','1M')->andReturn($viewRange);
        $this->_helper->shouldReceive('get')->with('frontpageAccounts',[])->andReturn([]);


        $this->action('GET', 'PreferencesController@index');
        $this->assertResponseOk();
    }

    public function testPostIndex()
    {
        $this->_helper->shouldReceive('set')->with('frontpageAccounts',[1]);
        $this->_helper->shouldReceive('set')->with('viewRange','1M');
        $this->action('POST', 'PreferencesController@postIndex',['frontpageAccounts' => [1],'viewRange' => '1M']);
        $this->assertResponseStatus(302);
    }
} 