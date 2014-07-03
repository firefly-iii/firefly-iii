<?php

class ProfileControllerTest extends TestCase
{

    public function testIndex()
    {
        // mock:
        View::shouldReceive('make')->with('profile.index');

        // call
        $this->call('GET', '/profile');

        // test
        $this->assertResponseOk();
    }

    public function testChangePassword()
    {
        // mock:
        View::shouldReceive('make')->with('profile.change-password');

        // call
        $this->call('GET', '/profile/change-password');

        // test
        $this->assertResponseOk();
    }

    public function testOldNoMatch()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn(new User);
        Hash::shouldReceive('check')->andReturn(false);

        $data = [
            'old'  => 'lala',
            'new1' => 'a',
            'new2' => 'a',
        ];


        // call
        $this->call('POST', '/profile/change-password', $data);

        // test
        $this->assertResponseOk();
        $this->assertSessionHas('error', 'Invalid current password!');
    }

    public function testNewEmpty()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn(new User);
        Hash::shouldReceive('check')->andReturn(true);

        $data = [
            'old'  => 'lala',
            'new1' => '',
            'new2' => '',
        ];


        // call
        $this->call('POST', '/profile/change-password', $data);

        // test
        $this->assertResponseOk();
        $this->assertSessionHas('error', 'Do fill in a password!');
    }

    public function testOldSame()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn(new User);
        Hash::shouldReceive('check')->andReturn(true);
        Hash::shouldReceive('make')->andReturn('blala');

        $data = [
            'old'  => 'a',
            'new1' => 'a',
            'new2' => 'a',
        ];


        // call
        $this->call('POST', '/profile/change-password', $data);

        // test
        $this->assertResponseOk();
        $this->assertSessionHas('error', 'The idea is to change your password.');
    }

    public function testNewNoMatch()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn(new User);
        Hash::shouldReceive('check')->andReturn(true);
        Hash::shouldReceive('make')->andReturn('blala');

        $data = [
            'old'  => 'b',
            'new1' => 'c',
            'new2' => 'd',
        ];


        // call
        $this->call('POST', '/profile/change-password', $data);

        // test
        $this->assertResponseOk();
        $this->assertSessionHas('error', 'New passwords do not match!');
    }

    public function testPostChangePassword()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn(new User);
        Hash::shouldReceive('check')->andReturn(true);
        Hash::shouldReceive('make')->andReturn('blala');

        $repository = $this->mock('Firefly\Storage\User\UserRepositoryInterface');
        $repository->shouldReceive('updatePassword')->once()->andReturn(true);

        $data = [
            'old'  => 'b',
            'new1' => 'c',
            'new2' => 'c',
        ];


        // call
        $this->call('POST', '/profile/change-password', $data);

        // test
        $this->assertRedirectedToRoute('profile');
        $this->assertSessionHas('success', 'Password changed!');
    }

} 