<?php

use Mockery as m;
use League\FactoryMuffin\Facade as f;


/**
 * Class PiggybankControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PiggybankControllerTest extends TestCase
{
    protected $_accounts;
    protected $_piggybanks;
    protected $_user;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_piggybanks = $this->mock('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

    }

    public function tearDown()
    {
        m::close();
    }

    public function testAddMoneyGET()
    {
        $piggyBank = f::create('Piggybank');
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_piggybanks->shouldReceive('leftOnAccount')->andReturn(1);

        $this->action('GET', 'PiggybankController@addMoney', $piggyBank->id);
        $this->assertResponseOk();

    }

    public function testCreatePiggybank()
    {
        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->once()->andReturn([]);
        $this->action('GET', 'PiggybankController@createPiggybank');
        $this->assertResponseOk();

    }

    public function testCreateRepeated()
    {
        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->once()->andReturn([]);
        $this->action('GET', 'PiggybankController@createRepeated');
        $this->assertResponseOk();

    }

    public function testDelete()
    {
        $piggyBank = f::create('Piggybank');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');


        $this->action('GET', 'PiggybankController@delete', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $piggyBank = f::create('Piggybank');
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        $this->_piggybanks->shouldReceive('destroy')->andReturn(true);
        Event::shouldReceive('fire')->with('piggybanks.destroy', [$piggyBank]);


        $this->action('POST', 'PiggybankController@destroy', $piggyBank->id);
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        $piggyBank = f::create('Piggybank');

        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->once()->andReturn([]);


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');


        $this->action('GET', 'PiggybankController@edit', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testEditRepeated()
    {
        $piggyBank = f::create('Piggybank');
        $piggyBank->repeats = 1;
        $piggyBank->save();


        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->once()->andReturn([]);


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'PiggybankController@edit', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        $aOne = f::create('Account');
        $aTwo = f::create('Account');

        $one = f::create('Piggybank');
        $one->account()->associate($aOne);
        $two = f::create('Piggybank');
        $two->account()->associate($aOne);
        $three = f::create('Piggybank');
        $three->account()->associate($aTwo);
        $this->_piggybanks->shouldReceive('get')->andReturn([$one, $two, $three]);
        $this->_piggybanks->shouldReceive('countRepeating')->andReturn(0);
        $this->_piggybanks->shouldReceive('leftOnAccount')->andReturn(0);
        $this->_piggybanks->shouldReceive('countNonrepeating')->andReturn(0);
        Event::shouldReceive('fire')->with('piggybanks.change');


        $this->action('GET', 'PiggybankController@index');
        $this->assertResponseOk();
    }

    public function testModifyMoneyAddPOST()
    {
        $piggyBank = f::create('Piggybank');
        $piggyBank->targetamount = 200;
        $piggyBank->save();
        $input = [
            $piggyBank->id,
            'amount' => 10.0,
            'what' => 'add'
        ];

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        Event::shouldReceive('fire');//->with('piggybanks.modifyAmountAdd', [$piggyBank, 10.0]);
        $this->_piggybanks->shouldReceive('modifyAmount')->once();

        $this->_piggybanks->shouldReceive('leftOnAccount')->once()->andReturn(200);


        $this->action('POST', 'PiggybankController@modMoney', $input);
        $this->assertSessionHas('success');
        $this->assertResponseStatus(302);

    }

    public function testModifyMoneyAddPOSTFails()
    {
        $piggyBank = f::create('Piggybank');
        $piggyBank->targetamount = 200;
        $piggyBank->save();
        $input = [
            $piggyBank->id,
            'amount' => 10.0,
            'what' => 'add'
        ];

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($piggyBank->account()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        Event::shouldReceive('fire')->with('piggybanks.modifyAmountAdd', [$piggyBank, -10.0]);
        $this->_piggybanks->shouldReceive('leftOnAccount')->once()->andReturn(5);


        $this->action('POST', 'PiggybankController@modMoney', $input);
        $this->assertSessionHas('warning');
        $this->assertResponseStatus(302);

    }

    /**
     * @expectedException \Firefly\Exception\FireflyException
     */
    public function testModifyMoneyPOSTException()
    {
        $piggyBank = f::create('Piggybank');
        $piggyBank->targetamount = 200;
        $piggyBank->save();
        $input = [
            $piggyBank->id,
            'amount' => 10.0,
            'what' => 'yomoma'
        ];

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($piggyBank->account()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');


        $this->action('POST', 'PiggybankController@modMoney', $input);
        $this->assertSessionHas('warning');
        $this->assertResponseStatus(302);


    }

    public function testModifyMoneyRemovePOST()
    {
        $pig = $this->mock('Piggybank');
        $piggybank = f::create('Piggybank');
        $rep = f::create('PiggybankRepetition');
        $rep->piggybank_id = $piggybank->id;
        $rep->save();


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $rep->piggybank()->first()->account()->first()->user_id
        );
        $pig->shouldReceive('currentRelevantRep')->andReturn($rep);
        $this->_piggybanks->shouldReceive('leftOnAccount')->andReturn(11);
        $this->_piggybanks->shouldReceive('modifyAmount')->once();

        $input = [
            $rep->piggybank()->first()->id,
            'amount' => 10.0,
            'what' => 'remove'
        ];

        $this->action('POST', 'PiggybankController@modMoney', $input);
        $this->assertSessionHas('success');
        $this->assertResponseStatus(302);

    }

    public function testModifyMoneyRemovePOSTFails()
    {
        $pig = $this->mock('Piggybank');
        $piggybank = f::create('Piggybank');
        $rep = f::create('PiggybankRepetition');
        $rep->piggybank_id = $piggybank->id;
        $rep->currentAmount = 5;
        $rep->save();


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $rep->piggybank()->first()->account()->first()->user_id
        );
        $pig->shouldReceive('currentRelevantRep')->andReturn($rep);

        $input = [
            $rep->piggybank()->first()->id,
            'amount' => 10.0,
            'what' => 'remove'
        ];

        $this->action('POST', 'PiggybankController@modMoney', $input);
        $this->assertSessionHas('warning');
        $this->assertResponseStatus(302);

    }

    public function teststorePiggybank()
    {
        $piggy = f::create('Piggybank');
        $this->_piggybanks->shouldReceive('store')->once()->andReturn($piggy);
        $this->action('POST', 'PiggybankController@storePiggybank');
        $this->assertResponseStatus(302);
    }

    public function testStoreRepeated()
    {
        $piggy = f::create('Piggybank');
        $this->_piggybanks->shouldReceive('store')->once()->andReturn($piggy);
        $this->action('POST', 'PiggybankController@storeRepeated');
        $this->assertResponseStatus(302);
    }

    public function teststorePiggybankFails()
    {
        $piggy = f::create('Piggybank');
        unset($piggy->id);
        $this->_piggybanks->shouldReceive('store')->once()->andReturn($piggy);
        $this->action('POST', 'PiggybankController@storePiggybank');
        $this->assertResponseStatus(302);
    }

    public function testStoreRepeatedFails()
    {
        $piggy = f::create('Piggybank');
        unset($piggy->id);
        $this->_piggybanks->shouldReceive('store')->once()->andReturn($piggy);
        $this->action('POST', 'PiggybankController@storeRepeated');
        $this->assertResponseStatus(302);
    }

    public function testRemoveMoneyGET()
    {
        $pig = $this->mock('Piggybank');
        $piggybank = f::create('Piggybank');
        $rep = f::create('PiggybankRepetition');
        $rep->piggybank_id = $piggybank->id;
        $rep->save();


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $rep->piggybank()->first()->account()->first()->user_id
        );
        $pig->shouldReceive('currentRelevantRep')->andReturn($rep);

        $this->_piggybanks->shouldReceive('leftOnAccount')->andReturn(1)->once();

        $this->action('GET', 'PiggybankController@removeMoney', $piggybank->id);
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $piggyBank = f::create('Piggybank');
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->andReturn('some@email');

        $this->action('GET', 'PiggybankController@show', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testUpdate()
    {
        $piggyBank = f::create('Piggybank');

        $this->_piggybanks->shouldReceive('update')->andReturn($piggyBank);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        Event::shouldReceive('fire')->with('piggybanks.update',[$piggyBank]);

        $this->action('POST', 'PiggybankController@update', $piggyBank->id);
        $this->assertResponseStatus(302);
    }

    public function testUpdateFails()
    {
        $piggyBank = f::create('Piggybank');
        unset($piggyBank->name);

        $this->_piggybanks->shouldReceive('update')->andReturn($piggyBank);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        Event::shouldReceive('fire')->with('piggybanks.change');

        $this->action('POST', 'PiggybankController@update', $piggyBank->id);
        $this->assertResponseStatus(302);
    }


}