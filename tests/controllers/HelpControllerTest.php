<?php

use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class HelpControllerTest
 */
class HelpControllerTest extends TestCase
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
     * Everything present and accounted for, and in cache:
     */
    public function testGetHelpText()
    {
        // login
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        // mock some stuff.
        $interface = $this->mock('FireflyIII\Helpers\Help\HelpInterface');
        $interface->shouldReceive('hasRoute')->once()->with('accounts.index')->andReturn(true);
        $interface->shouldReceive('getFromCache')->once()->with('help.accounts.index.title')->andReturn('Title.');
        $interface->shouldReceive('getFromCache')->once()->with('help.accounts.index.text')->andReturn('Text');
        $interface->shouldReceive('inCache')->andReturn(true);


        $this->call('GET', '/help/accounts.index');
        $this->assertResponseOk();
    }

    /**
     * Everything present and accounted for, but not cached
     */
    public function testGetHelpTextNoCache()
    {
        // login
        $user    = FactoryMuffin::create('FireflyIII\User');
        $content = ['title' => 'Bla', 'text' => 'Bla'];

        $this->be($user);
        // mock some stuff.
        $interface = $this->mock('FireflyIII\Helpers\Help\HelpInterface');
        $interface->shouldReceive('hasRoute')->once()->with('accounts.index')->andReturn(true);
        $interface->shouldReceive('getFromGithub')->once()->with('accounts.index')->andReturn($content);
        $interface->shouldReceive('putInCache')->once()->withArgs(['accounts.index', $content]);
        $interface->shouldReceive('inCache')->once()->andReturn(false);


        $this->call('GET', '/help/accounts.index');
        $this->assertResponseOk();
    }

    /**
     * No such route.
     */
    public function testGetHelpTextNoRoute()
    {
        // login
        $user = FactoryMuffin::create('FireflyIII\User');

        $this->be($user);
        // mock some stuff.
        $interface = $this->mock('FireflyIII\Helpers\Help\HelpInterface');
        $interface->shouldReceive('hasRoute')->once()->with('accounts.index')->andReturn(false);


        $this->call('GET', '/help/accounts.index');
        $this->assertResponseOk();
    }
}