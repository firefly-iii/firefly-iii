<?php
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class NewUserControllerTest
 */
class NewUserControllerTest extends TestCase
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
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers FireflyIII\Http\Controllers\NewUserController::index
     */
    public function testIndex()
    {
        $user       = FactoryMuffin::create('FireflyIII\User');
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        $this->be($user);

        // mock ALL THE THINGS!
        $repository->shouldReceive('countAccounts')->once()->andReturn(0);

        // language preference:
        $language       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);

        $lastActivity       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $lastActivity->data = microtime();
        Preferences::shouldReceive('lastActivity')->andReturn($lastActivity);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('X');

        $this->call('GET', '/new-user');
        $this->assertResponseStatus(200);
    }

    public function testIndexNoAccounts()
    {
        $user       = FactoryMuffin::create('FireflyIII\User');
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');


        $this->be($user);

        // mock ALL THE THINGS!
        $repository->shouldReceive('countAccounts')->once()->andReturn(3);

        // language preference:
        $language       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);

        $lastActivity       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $lastActivity->data = microtime();
        Preferences::shouldReceive('lastActivity')->andReturn($lastActivity);

        $this->call('GET', '/new-user');
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('index');

    }

    public function testPostIndex()
    {
        $user       = FactoryMuffin::create('FireflyIII\User');
        $currency   = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $account    = FactoryMuffin::create('FireflyIII\Models\Account');
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $this->be($user);

        $data = [
            '_token'              => 'replaceMe',
            'bank_name'           => 'Some Bank',
            'bank_balance'        => '100',
            'balance_currency_id' => $currency->id,
            'savings_balance'     => '100',
            'credit_card_limit'   => '100',

        ];

        $repository->shouldReceive('store')->andReturn($account);

        $this->call('POST', '/new-user/submit', $data);
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('index');

    }

}
