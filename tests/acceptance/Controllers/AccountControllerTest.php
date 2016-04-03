<?php
/**
 * AccountControllerTest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
{
    /**
     * @covers       FireflyIII\Http\Controllers\AccountController::create
     * @covers       FireflyIII\Http\Controllers\AccountController::__construct
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testCreate($range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $this->call('GET', '/accounts/create/asset');
        $this->assertResponseStatus(200);
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $this->call('GET', '/accounts/delete/1');
        $this->assertResponseStatus(200);
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::destroy
     */
    public function testDestroy()
    {
        $this->be($this->user());
        $this->session(['accounts.delete.url' => 'http://localhost']);
        $this->call('POST', '/accounts/destroy/6');
        $this->assertSessionHas('success');
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $this->call('GET', '/accounts/edit/1');
        $this->assertResponseStatus(200);
    }

    /**
     * @covers       FireflyIII\Http\Controllers\AccountController::index
     * @covers       FireflyIII\Http\Controllers\AccountController::isInArray
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndex($range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $this->call('GET', '/accounts/asset');
        $this->assertResponseStatus(200);
    }

    /**
     * @covers       FireflyIII\Http\Controllers\AccountController::show
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testShow($range)
    {
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getJournals')->once()->andReturn(new LengthAwarePaginator([], 0, 50));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $this->call('GET', '/accounts/show/1');
        $this->assertResponseStatus(200);
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::store
     * @covers FireflyIII\Http\Requests\AccountFormRequest::authorize
     * @covers FireflyIII\Http\Requests\AccountFormRequest::rules
     *
     */
    public function testStore()
    {
        $this->be($this->user());
        $this->session(['accounts.create.url' => 'http://localhost']);
        $args = [
            'name'                              => 'Some kind of test account.',
            'what'                              => 'asset',
            'amount_currency_id_virtualBalance' => 1,
            'amount_currency_id_openingBalance' => 1,
        ];

        $this->call('POST', '/accounts/store', $args);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::update
     * @covers FireflyIII\Http\Requests\AccountFormRequest::authorize
     * @covers FireflyIII\Http\Requests\AccountFormRequest::rules
     */
    public function testUpdate()
    {
        $this->session(['accounts.edit.url' => 'http://localhost']);
        $args = [
            'id'     => 1,
            'name'   => 'TestData new name',
            'active' => 1,
        ];
        $this->be($this->user());

        $this->call('POST', '/accounts/update/1', $args);
        $this->assertResponseStatus(302);

        $this->assertSessionHas('success');

    }
}
