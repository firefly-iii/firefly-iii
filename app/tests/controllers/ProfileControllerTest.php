<?php
use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class ProfileControllerTest
 */
class ProfileControllerTest extends TestCase
{
    protected $_user;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');

    }

    public function tearDown()
    {
        m::close();
    }


    public function testChangePassword()
    {
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($this->_user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'ProfileController@changePassword');
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($this->_user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'ProfileController@index');
        $this->assertResponseOk();
    }

    public function testPostChangePasswordDifferentNew()
    {
        $user = f::create('User');
        // for binding
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn($user->email);
        $this->_user->shouldReceive('getAttribute')->with('password')->andReturn($user->password);

        $this->action(
            'POST', 'ProfileController@postChangePassword',
            ['old' => 'sander', 'new1' => 'sander1', 'new2' => 'sander2']
        );
        $this->assertResponseOk();
    }

    public function testPostChangePasswordOK()
    {
        $user = f::create('User');
        // for binding
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn($user->email);
        $this->_user->shouldReceive('getAttribute')->with('password')->andReturn($user->password);

        $this->action(
            'POST', 'ProfileController@postChangePassword',
            ['old' => 'sander', 'new1' => 'sander2', 'new2' => 'sander2']
        );
        $this->assertResponseStatus(302);
    }


    public function testPostChangePasswordNoCurrent()
    {
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($this->_user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        $this->_user->shouldReceive('getAttribute')->with('password')->andReturn('Blablabla');

        $this->action('POST', 'ProfileController@postChangePassword');
        $this->assertResponseOk();
    }

    public function testPostChangePasswordNoMatchNew()
    {
        $user = f::create('User');
        // for binding
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn($user->email);
        $this->_user->shouldReceive('getAttribute')->with('password')->andReturn($user->password);

        $this->action(
            'POST', 'ProfileController@postChangePassword', ['old' => 'sander', 'new1' => 'sander', 'new2' => 'sander']
        );
        $this->assertResponseOk();
    }

    public function testPostChangePasswordSame()
    {
        $user = f::create('User');
        // for binding
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($user->id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn($user->email);
        $this->_user->shouldReceive('getAttribute')->with('password')->andReturn($user->password);

        $this->action('POST', 'ProfileController@postChangePassword', ['old' => 'sander']);
        $this->assertResponseOk();
    }

} 