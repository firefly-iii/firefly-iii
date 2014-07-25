<?php

use Mockery as m;

class AccountControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    public function testIndex()
    {
        // mock account type(s):
        $personal = $this->mock('AccountType');
        $personal->shouldReceive('getAttribute', 'description')->andReturn('Default account');

        $bene = $this->mock('AccountType');
        $bene->shouldReceive('getAttribute', 'description')->andReturn('Beneficiary account');

        $initial = $this->mock('AccountType');
        $initial->shouldReceive('getAttribute', 'description')->andReturn('Initial balance account');

        $cash = $this->mock('AccountType');
        $cash->shouldReceive('getAttribute', 'description')->andReturn('Cash account');


        // mock account(s)
        $one = $this->mock('Account');
        $one->shouldReceive('getAttribute')->andReturn($personal);

        $two = $this->mock('Account');
        $two->shouldReceive('getAttribute')->andReturn($bene);

        $three = $this->mock('Account');
        $three->shouldReceive('getAttribute')->andReturn($initial);

        $four = $this->mock('Account');
        $four->shouldReceive('getAttribute')->andReturn($cash);
        $c = new \Illuminate\Database\Eloquent\Collection([$one, $two, $three, $four]);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('get')->andReturn($c);


        $list = [
            'personal'      => [$one],
            'beneficiaries' => [$two],
            'initial'       => [$three],
            'cash'          => [$four]
        ];

        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('accounts.index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('accounts', $list)->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('total', 4)->andReturn(\Mockery::self());


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