<?php

class AccountControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testIndex()
    {

        $list = [
            'personal'      => [],
            'beneficiaries' => [],
            'initial'       => [],
            'cash'          => []
        ];


        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('accounts.index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('accounts', $list)->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('total', 0)->andReturn(\Mockery::self());

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('get')->andReturn([]);

        // call
        $this->call('GET', '/accounts');

        // test
        $this->assertResponseOk();

    }

    public function testCreate()
    {
        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('accounts.create');

        // call
        $this->call('GET', '/accounts/create');

        // test
        $this->assertResponseOk();
    }

    public function testShow()
    {
        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('get')->with(1)->andReturn([]);

        // call
        $this->call('GET', '/accounts/1');

        // test
        $this->assertResponseOk();
    }


    public function tearDown()
    {
        Mockery::close();
    }
}