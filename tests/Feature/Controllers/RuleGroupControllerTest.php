<?php
/**
 * RuleGroupControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

class RuleGroupControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('rule-groups.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('rule-groups.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::destroy
     */
    public function testDestroy()
    {
        $repository = $this->mock(RuleGroupRepositoryInterface::class);
        $repository->shouldReceive('destroy');

        $this->session(['rule-groups.delete.url' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('rule-groups.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertRedirectedToRoute('index');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::down
     */
    public function testDown()
    {
        $this->be($this->user());
        $response = $this->get(route('rule-groups.down', [1]));
        $response->assertStatus(302);
        $this->assertRedirectedToRoute('rules.index');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('rule-groups.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::execute
     */
    public function testExecute()
    {
        $this->session(['first' => new Carbon('2010-01-01')]);
        $data = [
            'accounts'   => [1],
            'start_date' => '2010-01-02',
            'end_date'   => '2010-01-02',
        ];
        $this->be($this->user());
        $response = $this->post(route('rule-groups.execute', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertRedirectedToRoute('rules.index');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::selectTransactions
     */
    public function testSelectTransactions()
    {
        $this->be($this->user());
        $response = $this->get(route('rule-groups.select-transactions', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::store
     */
    public function testStore()
    {
        $this->session(['rule-groups.create.url' => 'http://localhost']);
        $data = [
            'title'       => 'A',
            'description' => '',
        ];

        $repository = $this->mock(RuleGroupRepositoryInterface::class);
        $repository->shouldReceive('store')->andReturn(new RuleGroup);
        $repository->shouldReceive('find')->andReturn(new RuleGroup);

        $this->be($this->user());
        $response = $this->post(route('rule-groups.store', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::up
     */
    public function testUp()
    {
        $this->be($this->user());
        $response = $this->get(route('rule-groups.up', [1]));
        $response->assertStatus(302);
        $this->assertRedirectedToRoute('rules.index');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::update
     */
    public function testUpdate()
    {
        $data = [
            'title'              => 'C',
            'description'            => 'XX',
        ];
        $this->session(['rule-groups.edit.url' => 'http://localhost']);

        $repository = $this->mock(RuleGroupRepositoryInterface::class);
        $repository->shouldReceive('update');
        $repository->shouldReceive('find')->andReturn(new RuleGroup);

        $this->be($this->user());
        $response = $this->post(route('rule-groups.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}