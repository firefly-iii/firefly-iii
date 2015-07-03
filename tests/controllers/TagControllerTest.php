<?php
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * Class TagControllerTest
 */
class TagControllerTest extends TestCase
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
     * @covers FireflyIII\Http\Controllers\TagController::create
     */
    public function testCreate()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/tags/create');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::delete
     */
    public function testDelete()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $this->call('GET', '/tags/delete/' . $tag->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::destroy
     */
    public function testDestroy()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $this->call('POST', '/tags/destroy/' . $tag->id, ['_token' => 'replaceMe']);
        $this->assertSessionHas('success');
        $this->assertResponseStatus(302);

    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::edit
     */
    public function testEdit()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $this->call('GET', '/tags/edit/' . $tag->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::edit
     */
    public function testEditBalancingAct()
    {
        $tag        = FactoryMuffin::create('FireflyIII\Models\Tag');
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $type       = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $type->type = 'Transfer';
        $type->save();
        $journal->transactionType()->associate($type);
        $journal->save();
        $tag->transactionJournals()->save($journal);
        $tag->tagMode = 'balancingAct';
        $tag->save();
        $this->be($tag->user);

        $this->call('GET', '/tags/edit/' . $tag->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::edit
     */
    public function testEditThreeExpenses()
    {
        $tag        = FactoryMuffin::create('FireflyIII\Models\Tag');
        $type       = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $type->type = 'Withdrawal';
        $type->save();

        for ($i = 0; $i < 3; $i++) {
            $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->transactionType()->associate($type);
            $journal->save();
            $tag->transactionJournals()->save($journal);
        }


        $tag->tagMode = 'nothing';
        $tag->save();
        $this->be($tag->user);

        $this->call('GET', '/tags/edit/' . $tag->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::hideTagHelp
     */
    public function testHideTagHelp()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $this->call('POST', '/tags/hideTagHelp/true', ['_token' => 'replaceMe']);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::index
     */
    public function testIndex()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $this->call('GET', '/tags');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::edit
     */
    public function testMultipleDeposits()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        for ($i = 0; $i < 3; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->transaction_type_id = $type->id;
            $journal->save();
            $tag->transactionJournals()->save($journal);
        }


        $tag->tagMode = 'nothing';
        $tag->save();
        $this->be($tag->user);

        $this->call('GET', '/tags/edit/' . $tag->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::show
     */
    public function testShow()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $this->call('GET', '/tags/show/' . $tag->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::store
     */
    public function testStore()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $data = [
            '_token'  => 'replaceMe',
            'tag'     => 'BlaBla' . rand(1, 1000),
            'tagMode' => 'nothing'
        ];

        $this->call('POST', '/tags/store/', $data);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::store
     */
    public function testStoreWithLocation()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $data = [
            '_token'         => 'replaceMe',
            'tag'            => 'BlaBla' . rand(1, 1000),
            'tagMode'        => 'nothing',
            'latitude'       => 12,
            'longitude'      => 13,
            'zoomLevel'      => 3,
            'setTag'         => 'true',
            'create_another' => 1,
        ];

        $this->call('POST', '/tags/store/', $data);
        $this->assertResponseStatus(302);
    }

    public function testUpdate()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $data = [
            '_token'  => 'replaceMe',
            'tag'     => 'BlaBla' . rand(1, 1000),
            'tagMode' => 'nothing',
            'id'      => $tag->id,
        ];

        $this->call('POST', '/tags/update/' . $tag->id, $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    public function testUpdateNoNameChange()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $data = [
            '_token'  => 'replaceMe',
            'tag'     => $tag->tag,
            'tagMode' => 'nothing',
            'id'      => $tag->id,
        ];

        $this->call('POST', '/tags/update/' . $tag->id, $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    /**
     * @covers FireflyIII\Http\Controllers\TagController::update
     */
    public function testUpdateWithLocation()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $this->be($tag->user);

        $data = [
            '_token'         => 'replaceMe',
            'tag'            => 'BlaBla' . rand(1, 1000),
            'tagMode'        => 'nothing',
            'id'             => $tag->id,
            'latitude'       => 12,
            'setTag'         => 'true',
            'longitude'      => 13,
            'zoomLevel'      => 3,
            'return_to_edit' => 1,
        ];

        $this->call('POST', '/tags/update/' . $tag->id, $data);
        $this->assertResponseStatus(302);
    }


}
