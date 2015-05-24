<?php

use FireflyIII\Models\Category;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
 * Class CategoryModelTest
 */
class CategoryModelTest extends TestCase
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
     * @covers FireflyIII\Models\Category::firstOrCreateEncrypted
     */
    public function testFirstOrCreateEncrypted()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');

        $search = [
            'name'    => $category->name,
            'user_id' => $category->user_id
        ];

        $result = Category::firstOrCreateEncrypted($search);

        $this->assertEquals($result->id, $category->id);
    }

    /**
     * @covers FireflyIII\Models\Category::firstOrCreateEncrypted
     */
    public function testFirstOrCreateEncryptedNew()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');

        $search = [
            'name'    => 'Some category name',
            'user_id' => $category->user_id
        ];

        $result = Category::firstOrCreateEncrypted($search);

        $this->assertNotEquals($result->id, $category->id);
    }

}