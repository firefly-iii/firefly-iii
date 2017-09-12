<?php
/**
 * RuleGroupControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Jobs\ExecuteRuleGroupOnExistingTransactions;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class RuleGroupControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleGroupControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::create
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::__construct
     */
    public function testCreate()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('get')->andReturn(new Collection);

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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('destroy');

        $this->session(['rule-groups.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('rule-groups.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::down
     */
    public function testDown()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('moveDown');

        $this->be($this->user());
        $response = $this->get(route('rule-groups.down', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);


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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection);

        $this->expectsJobs(ExecuteRuleGroupOnExistingTransactions::class);

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
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::selectTransactions
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::__construct
     */
    public function testSelectTransactions()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->session(['rule-groups.create.uri' => 'http://localhost']);
        $repository->shouldReceive('store')->andReturn(new RuleGroup);
        $repository->shouldReceive('find')->andReturn(new RuleGroup);
        $data = [
            'title'       => 'A',
            'description' => 'No description',
        ];


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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('moveUp');

        $this->be($this->user());
        $response = $this->get(route('rule-groups.up', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroupController::update
     */
    public function testUpdate()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [
            'title'       => 'C',
            'description' => 'XX',
        ];
        $this->session(['rule-groups.edit.uri' => 'http://localhost']);

        $repository->shouldReceive('update');
        $repository->shouldReceive('find')->andReturn(new RuleGroup);

        $this->be($this->user());
        $response = $this->post(route('rule-groups.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
