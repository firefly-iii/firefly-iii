<?php
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class AuthControllerTest
 */
class AuthControllerTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        FactoryMuffin::create('FireflyIII\User');
    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

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
     * @covers FireflyIII\Http\Controllers\Auth\AuthController::postRegister
     */
    public function testPostRegister()
    {

        $data = [
            'email'                 => 'test@example.com',
            'password'              => 'onetwothree',
            'password_confirmation' => 'onetwothree',
            '_token'                => 'replaceMe'
        ];
        $this->call('POST', '/auth/register', $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    /**
     * @covers FireflyIII\Http\Controllers\Auth\AuthController::postRegister
     */
    public function testPostRegisterFails()
    {

        $data = [
            'email'                 => 'test@example.com',
            'password'              => 'onetwothree',
            'password_confirmation' => 'onetwofour',
            '_token'                => 'replaceMe'
        ];
        $this->call('POST', '/auth/register', $data);
        $this->assertResponseStatus(302);


    }

}
