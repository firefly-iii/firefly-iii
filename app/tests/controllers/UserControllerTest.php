<?php

class UserControllerTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testLogin()
    {
        // mock:
        View::shouldReceive('make')->with('user.login');

        // call
        $this->call('GET', '/login');

        // test
    }

    public function testPostLogin()
    {
        // data:
        $data = ['email' => 'bla@bla.nl', 'password' => 'xxxx', 'remember_me' => '1'];

        // mock
        Auth::shouldReceive('attempt')->once()->andReturn(true);

        // test
        $this->call('POST', '/login', $data);
        $this->assertSessionHas('success');
        $this->assertRedirectedToRoute('index');
    }

    public function testPostFalseLogin()
    {
        // data
        $data = ['email' => 'bla@bla.nl', 'password' => 'xxxx', 'remember_me' => '1'];

        // mock
        Auth::shouldReceive('attempt')->once()->andReturn(false);
        View::shouldReceive('make')->with('user.login')->once();

        // test
        $this->call('POST', '/login', $data);
        $this->assertSessionHas('error');
    }

    public function testRegister()
    {
        // no mock for config! :(
        Config::set('auth.allow_register', true);
        // test
        $this->call('GET', '/register');
        $this->assertResponseOk();
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRegisterNotAllowed()
    {
        // no mock for config! :(
        Config::set('auth.allow_register', false);
        // test
        $this->call('GET', '/register');
        $this->assertResponseStatus(404);
    }

    /**
     * Register and verify:
     */
    public function testPostRegisterAllowed()
    {
        // no mock for config! :(
        Config::set('auth.verify_mail', true);
        Config::set('auth.allow_register', true);

        // mock repository:
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');
        $repository->shouldReceive('register')->once()->andReturn(new User);
        $email->shouldReceive('sendVerificationMail')->once()->andReturn(true);
        // data:
        $data = [
            'email' => 'bla@bla'
        ];

        // test
        $crawler = $this->client->request('POST', '/register', $data);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Verification pending")'));

    }

    /**
     * Register and NO verify:
     */
    public function testPostRegisterNoVerifyAllowed()
    {
        // no mock for config! :(
        Config::set('auth.verify_mail', false);
        Config::set('auth.allow_register', true);

        // mock repository:
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');
        $repository->shouldReceive('register')->once()->andReturn(new User);
        $email->shouldReceive('sendPasswordMail')->once()->andReturn(true);
        // data:
        $data = [
            'email' => 'bla@bla'
        ];

        // test
        $crawler = $this->client->request('POST', '/register', $data);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Registered!")'));

    }

    public function mock($class)
    {
        $mock = Mockery::mock($class);

        $this->app->instance($class, $mock);

        return $mock;
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testPostRegisterNotAllowed()
    {
        // no mock for config! :(
        Config::set('auth.verify_mail', true);
        Config::set('auth.verify_reset', true);
        Config::set('auth.allow_register', false);

        // mock repository:
        $data = [
            'email' => 'bla@bla'
        ];

        // test
        $this->call('POST', '/register', $data);
        $this->assertResponseStatus(404);

    }

    public function tearDown()
    {
        Mockery::close();
    }

}