<?php

use FireflyIII\Models\Tag;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class TagModelTest
 */
class TagModelTest extends TestCase
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
     * @covers FireflyIII\Models\Tag::firstOrCreateEncrypted
     */
    public function testFirstOrCreateEncryptedNew()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');

        $search = [
            'tagMode' => 'something',
            'tag'     => 'Something else',
            'user_id' => $tag->user_id,
        ];

        $result = Tag::firstOrCreateEncrypted($search);

        $this->assertNotEquals($tag->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\Tag::firstOrCreateEncrypted
     */
    public function testFirstOrCreateEncrypted()
    {
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');

        $search = [
            'tagMode' => 'something',
            'tag'     => $tag->tag,
            'user_id' => $tag->user_id,
        ];

        $result = Tag::firstOrCreateEncrypted($search);

        $this->assertEquals($tag->id, $result->id);
    }

}