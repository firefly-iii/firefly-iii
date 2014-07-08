<?php

class ChartControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    public function testHomeAccount()
    {
        // mock preference:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn('week');

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('viewRange', 'week')->once()->andReturn($pref);

        // mock toolkit:
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn(null);

        // create a semi-mocked collection of accounts:

        // mock account(s)
        $personal = $this->mock('AccountType');
        $personal->shouldReceive('jsonSerialize')->andReturn('');

        $one = $this->mock('Account');
        $one->shouldReceive('getAttribute')->andReturn($personal);
        $one->shouldReceive('balance')->andReturn(0);

        // collection:
        $c = new \Illuminate\Database\Eloquent\Collection([$one]);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefault')->andReturn($c);


        // call
        $this->call('GET', '/chart/home/account');

        // test
        $this->assertResponseOk();
    }

    public function testHomeAccountWithInput()
    {
        // save actual account:
        $type = new AccountType;
        $type->description = 'An account';
        $type->save();

        $user = new User;
        $user->email = 'bla';
        $user->migrated = false;
        $user->password = 'bla';
        $user->save();
        $account = new Account;
        $account->accountType()->associate($type);
        $account->user()->associate($user);
        $account->name = 'Hello';
        $account->active = true;
        $account->save();

        // mock preference:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn('week');

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('viewRange', 'week')->once()->andReturn($pref);

        // mock toolkit:
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn(null);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('find')->with(1)->andReturn($account);

        // call
        $this->call('GET', '/chart/home/account/' . $account->id);

        // test
        $this->assertResponseOk();
    }

    public function testHomeAccountWithInvalidInput()
    {
        // save actual account:
        $type = new AccountType;
        $type->description = 'An account';
        $type->save();

        $user = new User;
        $user->email = 'bla';
        $user->migrated = false;
        $user->password = 'bla';
        $user->save();
        $account = new Account;
        $account->accountType()->associate($type);
        $account->user()->associate($user);
        $account->name = 'Hello';
        $account->active = true;
        $account->save();

        // mock preference:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn('week');

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('viewRange', 'week')->once()->andReturn($pref);

        // mock toolkit:
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn(null);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('find')->with(1)->andReturn(null);

        // call
        $this->call('GET', '/chart/home/account/' . $account->id);

        // test
        $this->assertResponseOk();
        $this->assertViewHas('message');
    }
}