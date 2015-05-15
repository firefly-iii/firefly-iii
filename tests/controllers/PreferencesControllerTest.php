<?php

use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class PreferencesControllerTest
 */
class PreferencesControllerTest extends TestCase
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

    public function testIndex()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $pref     = FactoryMuffin::create('FireflyIII\Models\Preference');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $this->be($user);


        // mock:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // fake!
        $repository->shouldReceive('getAccounts')->with(['Default account', 'Asset account'])->andReturn(new Collection);
        Preferences::shouldReceive('get')->once()->withArgs(['viewRange', '1M'])->andReturn($pref);
        Preferences::shouldReceive('get')->once()->withArgs(['frontPageAccounts', []])->andReturn($pref);
        Preferences::shouldReceive('get')->once()->withArgs(['budgetMaximum', 1000])->andReturn($pref);
        Preferences::shouldReceive('get')->once()->withArgs(['currencyPreference', 'EUR'])->andReturn($pref);
        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getAllCurrencies')->andReturn(new Collection);
        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency);

        // language preference:
        $language = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);

        $this->call('GET', '/preferences');
        $this->assertResponseOk();
    }

    public function testPostIndex()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $data = [
            'frontPageAccounts' => [1, 2, 3],
            '_token'            => 'replaceMe',
            'viewRange'         => '1M',
            'language' => 'en',
        ];

        // language preference:
        $language = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);

        Preferences::shouldReceive('set')->once()->withArgs(['frontPageAccounts', [1, 2, 3]]);
        Preferences::shouldReceive('set')->once()->withArgs(['viewRange', '1M']);
        Preferences::shouldReceive('set')->once()->withArgs(['budgetMaximum', 0]);
        Preferences::shouldReceive('set')->once()->withArgs(['language', 'en']);

        // language preference:
        $language = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);


        $this->call('POST', '/preferences', $data);
        $this->assertResponseStatus(302);
    }
}
