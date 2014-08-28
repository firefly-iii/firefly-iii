<?php

use League\FactoryMuffin\Facade as f;
use Mockery as m;


/**
 * Class UserControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class UserControllerTest extends TestCase
{
    protected $_user;
    protected $_users;
    protected $_email;

    public function setUp()
    {

        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_users = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $this->_email = $this->mock('Firefly\Helper\Email\EmailHelperInterface');

    }

    public function tearDown()
    {
        m::close();
    }

    public function testLogin()
    {
        $this->action('GET', 'UserController@login');
        $this->assertResponseOk();
    }

    public function testLogout()
    {
        $this->action('GET', 'UserController@logout');
        $this->assertResponseStatus(302);
    }

    public function testPostLogin()
    {
        $input = [
            'email'    => 'bla@bla',
            'password' => 'something',
        ];

        Auth::shouldReceive('attempt')->with($input, false)->andReturn(true);

        $this->action('POST', 'UserController@postLogin', $input);
        $this->assertResponseStatus(302);
    }

    public function testPostLoginFails()
    {

        $this->action('POST', 'UserController@postLogin');
        $this->assertResponseOk();
    }

    public function testPostRegister()
    {
        Config::set('auth.allow_register', true);
        $user = f::create('User');
        $this->_users->shouldReceive('register')->andReturn($user);
        $this->_email->shouldReceive('sendPasswordMail')->with($user);
        $this->action('POST', 'UserController@postRegister');
        $this->assertResponseOk();
    }

    public function testPostRegisterFails()
    {
        Config::set('auth.allow_register', true);
        $this->_users->shouldReceive('register')->andReturn(false);
        $this->action('POST', 'UserController@postRegister');
        $this->assertResponseOk();
    }

    public function testPostRegisterNotAllowed()
    {
        Config::set('auth.allow_register', false);
        $this->action('POST', 'UserController@postRegister');
        $this->assertResponseOk();
    }

    public function testPostRegisterVerify()
    {
        Config::set('auth.allow_register', true);
        Config::set('auth.verify_mail', true);
        $user = f::create('User');
        $this->_users->shouldReceive('register')->andReturn($user);
        $this->_email->shouldReceive('sendVerificationMail')->with($user);
        $this->action('POST', 'UserController@postRegister');
        $this->assertResponseOk();
    }

    public function testPostRemindme()
    {
        $user = f::create('User');
        Config::set('auth.verify_reset', true);
        $this->_users->shouldReceive('findByEmail')->andReturn($user);
        $this->_email->shouldReceive('sendResetVerification');
        $this->action('POST', 'UserController@postRemindme');
        $this->assertResponseOk();
    }

    public function testPostRemindmeNoVerify()
    {
        $user = f::create('User');
        Config::set('auth.verify_reset', false);
        $this->_users->shouldReceive('findByEmail')->andReturn($user);
        $this->_email->shouldReceive('sendPasswordMail');
        $this->action('POST', 'UserController@postRemindme');
        $this->assertResponseOk();
    }

    public function testPostRemindmeFails()
    {
        Config::set('auth.verify_reset', true);
        $this->_users->shouldReceive('findByEmail')->andReturn(false);
        $this->action('POST', 'UserController@postRemindme');
        $this->assertResponseOk();
    }

    public function testRegister()
    {
        $this->action('GET', 'UserController@register');
        $this->assertResponseOk();
    }

    public function testRegisterNotAllowed()
    {
        Config::set('auth.allow_register', false);
        $this->action('GET', 'UserController@register');
        $this->assertResponseOk();
    }

    public function testRemindme()
    {
        $this->action('GET', 'UserController@remindme');
        $this->assertResponseOk();
    }

    public function testReset()
    {
        $user = f::create('User');

        $this->_users->shouldReceive('findByReset')->andReturn($user);
        $this->_email->shouldReceive('sendPasswordMail');
        $this->action('GET', 'UserController@reset');
        $this->assertResponseOk();
    }

    public function testResetNoUser()
    {
        $this->_users->shouldReceive('findByReset')->andReturn(false);
        $this->action('GET', 'UserController@reset');
        $this->assertResponseOk();
    }
} 