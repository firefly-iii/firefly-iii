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

    public function mock($class)
    {
        $mock = Mockery::mock($class);

        $this->app->instance($class, $mock);

        return $mock;
    }

    /**
     * Register and verify FAILED:
     */
    public function testPostRegisterAllowedFailed()
    {
        // no mock for config! :(
        Config::set('auth.verify_mail', true);
        Config::set('auth.allow_register', true);

        // mock repository:
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');
        $repository->shouldReceive('register')->once()->andReturn(null);
        // test
        $crawler = $this->client->request('POST', '/register');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Register")'));

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
        $this->assertCount(1, $crawler->filter('h1:contains("Password sent")'));

    }

    public function testLogout()
    {

        Auth::shouldReceive('logout');

        $this->call('GET', '/logout');

    }

    public function testRemindme()
    {

        $this->call('GET', '/remindme');
        $this->assertResponseOk();
    }

    public function testPostRemindmeWithVerification()
    {
        Config::set('auth.verify_reset', true);
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');
        $repository->shouldReceive('findByEmail')->once()->andReturn(new User);
        $email->shouldReceive('sendResetVerification')->once()->andReturn(true);

        $this->call('POST', '/remindme');
        $this->assertResponseOk();

    }

    public function testPostRemindmeWithoutVerification()
    {
        Config::set('auth.verify_reset', false);
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');
        $repository->shouldReceive('findByEmail')->once()->andReturn(new User);
        $email->shouldReceive('sendPasswordMail')->once()->andReturn(true);

        $this->call('POST', '/remindme');
        $this->assertResponseOk();
    }

    public function testPostRemindmeFails()
    {
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $repository->shouldReceive('findByEmail')->once()->andReturn(null);

        $this->call('POST', '/remindme');
        $this->assertResponseOk();
        $this->assertSessionHas('error');
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

    public function testVerification()
    {

        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');

        $repository->shouldReceive('findByVerification')->once()->andReturn(new User);
        $email->shouldReceive('sendPasswordMail')->once()->andReturn(true);

        // test
        $crawler = $this->client->request('GET', '/verify/blabla');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Password sent")'));

    }

    public function testVerificationFails()
    {
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $repository->shouldReceive('findByVerification')->once()->andReturn(null);

        // test
        $crawler = $this->client->request('GET', '/verify/blabla');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Error")'));
        $this->assertViewHas('message');
    }

    public function testReset()
    {

        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $email = $this->mock('Firefly\Helper\Email\EmailHelper');

        $repository->shouldReceive('findByReset')->once()->andReturn(new User);
        $email->shouldReceive('sendPasswordMail')->once()->andReturn(true);

        // test
        $crawler = $this->client->request('GET', '/reset/blabla');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Password sent")'));

    }

    public function testResetFails()
    {
        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $repository->shouldReceive('findByReset')->once()->andReturn(null);

        // test
        $crawler = $this->client->request('GET', '/reset/blabla');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('h1:contains("Error")'));
        $this->assertViewHas('message');
    }

    public function tearDown()
    {
        Mockery::close();
    }

}