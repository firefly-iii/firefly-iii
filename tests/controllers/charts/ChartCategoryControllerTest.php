<?php

use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartCategoryControllerTest
 */
class ChartCategoryControllerTest extends TestCase
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
     * @covers FireflyIII\Http\Controllers\Chart\CategoryController::all
     */
    public function testAll()
    {

        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/chart/category/'.$category->id.'/all');
        $this->assertResponseOk();


    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\CategoryController::frontpage
     */
    public function testFrontpage()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // make data:
        $set = [
            ['name' => 'Something', 'sum' => 100],
            ['name' => 'Something Else', 'sum' => 200],
            ['name' => 'Something Else Entirely', 'sum' => 200]
        ];

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        // fake!
        $repository->shouldReceive('getCategoriesAndExpensesCorrected')->andReturn($set);

        //getCategoriesAndExpensesCorrected

        $this->call('GET', '/chart/category/frontpage');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\CategoryController::month
     */
    public function testMonth()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/chart/category/'.$category->id.'/month');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\CategoryController::year
     */
    public function testYear()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $categories = new Collection([FactoryMuffin::create('FireflyIII\Models\Category')]);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        // fake!
        $repository->shouldReceive('getCategories')->andReturn($categories);
        $repository->shouldReceive('spentInPeriodCorrected')->andReturn(0);

        $this->call('GET', '/chart/category/year/2015');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\CategoryController::year
     */
    public function testYearShared()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $categories = new Collection([FactoryMuffin::create('FireflyIII\Models\Category')]);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        // fake!
        $repository->shouldReceive('getCategories')->andReturn($categories);
        $repository->shouldReceive('spentInPeriodCorrected')->andReturn(0);

        $this->call('GET', '/chart/category/year/2015/shared');
        $this->assertResponseOk();
    }

}
