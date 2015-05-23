<?php
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ProfileControllerTest
 */
class ProfileControllerTest extends TestCase
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
     * @covers FireflyIII\Http\Controllers\ProfileController::changePassword
     */
    public function testChangePassword()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/profile/change-password');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::deleteAccount
     */
    public function testDeleteAccount()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/profile/delete-account');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::index
     */
    public function testIndex()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/profile');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::postChangePassword
     */
    public function testPostChangePassword()
    {
        $user           = FactoryMuffin::create('FireflyIII\User');
        $user->password = bcrypt('current');
        $user->save();
        $this->be($user);

        $post = [
            'current_password'          => 'current',
            'new_password'              => 'something',
            'new_password_confirmation' => 'something',
            '_token'                    => 'replaceMe'
        ];

        $this->call('POST', '/profile/change-password', $post);

        $this->assertRedirectedToRoute('profile');
        $this->assertSessionHas('success', 'Password changed!');
        $this->assertResponseStatus(302);

    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::postChangePassword
     */
    public function testPostChangePasswordInvalidCurrent()
    {
        $user           = FactoryMuffin::create('FireflyIII\User');
        $user->password = bcrypt('current');
        $user->save();
        $this->be($user);

        $post = [
            'current_password'          => 'currentWrong',
            'new_password'              => 'something',
            'new_password_confirmation' => 'something',
            '_token'                    => 'replaceMe'
        ];

        $this->call('POST', '/profile/change-password', $post);

        $this->assertRedirectedToRoute('change-password');
        $this->assertSessionHas('error', 'Invalid current password!');
        $this->assertResponseStatus(302);

    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::postChangePassword
     */
    public function testPostChangePasswordNoNewPassword()
    {
        $user           = FactoryMuffin::create('FireflyIII\User');
        $user->password = bcrypt('current');
        $user->save();
        $this->be($user);

        $post = [
            'current_password'          => 'current',
            'new_password'              => 'current',
            'new_password_confirmation' => 'current',
            '_token'                    => 'replaceMe'
        ];

        $this->call('POST', '/profile/change-password', $post);

        $this->assertSessionHas('error', 'The idea is to change your password.');
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('change-password');


    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::postDeleteAccount
     */
    public function testPostDeleteAccount()
    {
        $user           = FactoryMuffin::create('FireflyIII\User');
        $user->password = bcrypt('current');
        $user->save();
        $this->be($user);

        $post = [
            'password' => 'current',
            '_token'   => 'replaceMe'
        ];

        $this->call('POST', '/profile/delete-account', $post);

        $this->assertRedirectedToRoute('index');
        $this->assertResponseStatus(302);

    }

    /**
     * @covers FireflyIII\Http\Controllers\ProfileController::postDeleteAccount
     */
    public function testPostDeleteAccountInvalidPassword()
    {
        $user           = FactoryMuffin::create('FireflyIII\User');
        $user->password = bcrypt('current');
        $user->save();
        $this->be($user);

        $post = [
            'password' => 'currentXX',
            '_token'   => 'replaceMe'
        ];

        $this->call('POST', '/profile/delete-account', $post);

        $this->assertRedirectedToRoute('delete-account');
        $this->assertSessionHas('error', 'Invalid password!');
        $this->assertResponseStatus(302);

    }

}
