<?php
/**
 * RuleControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Rules\TransactionMatcher;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class RuleControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class RuleControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::create
     */
    public function testCreate()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('rules.create', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::create
     * @covers \FireflyIII\Http\Controllers\RuleController::getPreviousTriggers
     * @covers \FireflyIII\Http\Controllers\RuleController::getPreviousActions
     */
    public function testCreatePreviousInput()
    {
        $old = [
            'rule-trigger'       => ['description_is'],
            'rule-trigger-stop'  => ['1'],
            'rule-trigger-value' => ['X'],
            'rule-action'        => ['set_category'],
            'rule-action-stop'   => ['1'],
            'rule-action-value'  => ['x'],
        ];
        $this->session(['_old_input' => $old]);


        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('rules.create', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::delete
     */
    public function testDelete()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('rules.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::destroy
     */
    public function testDestroy()
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('destroy');

        $this->session(['rules.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('rules.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::down
     */
    public function testDown()
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('moveDown');

        $this->be($this->user());
        $response = $this->get(route('rules.down', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::edit
     * @covers \FireflyIII\Http\Controllers\RuleController::getCurrentActions
     * @covers \FireflyIII\Http\Controllers\RuleController::getCurrentTriggers
     */
    public function testEdit()
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getPrimaryTrigger')->andReturn(new Rule);

        $this->be($this->user());
        $response = $this->get(route('rules.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::edit
     * @covers \FireflyIII\Http\Controllers\RuleController::getPreviousActions
     * @covers \FireflyIII\Http\Controllers\RuleController::getPreviousTriggers
     */
    public function testEditPreviousInput()
    {
        $old = [
            'rule-trigger'       => ['description_is'],
            'rule-trigger-stop'  => ['1'],
            'rule-trigger-value' => ['X'],
            'rule-action'        => ['set_category'],
            'rule-action-stop'   => ['1'],
            'rule-action-value'  => ['x'],
        ];
        $this->session(['_old_input' => $old]);

        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getPrimaryTrigger')->andReturn(new Rule);

        $this->be($this->user());
        $response = $this->get(route('rules.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::index
     * @covers \FireflyIII\Http\Controllers\RuleController::__construct
     * @covers \FireflyIII\Http\Controllers\RuleController::createDefaultRule
     * @covers \FireflyIII\Http\Controllers\RuleController::createDefaultRuleGroup
     */
    public function testIndex()
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $ruleGroupRepos->shouldReceive('count')->andReturn(0);
        $ruleGroupRepos->shouldReceive('store');
        $repository->shouldReceive('getFirstRuleGroup')->andReturn(new RuleGroup);
        $ruleGroupRepos->shouldReceive('getRuleGroupsWithRules')->andReturn(new Collection);
        $repository->shouldReceive('count')->andReturn(0);
        $repository->shouldReceive('store');

        $this->be($this->user());
        $response = $this->get(route('rules.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::reorderRuleActions
     */
    public function testReorderRuleActions()
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = ['actions' => [1, 2, 3],];
        $repository->shouldReceive('reorderRuleActions')->once();

        $this->be($this->user());
        $response = $this->post(route('rules.reorder-actions', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::reorderRuleTriggers
     */
    public function testReorderRuleTriggers()
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $data = ['triggers' => [1, 2, 3],];
        $repository->shouldReceive('reorderRuleTriggers')->once();

        $this->be($this->user());
        $response = $this->post(route('rules.reorder-triggers', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::store
     */
    public function testStore()
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $ruleGroupRepos->shouldReceive('find')->andReturn(new RuleGroup)->once();
        $repository->shouldReceive('store')->andReturn(new Rule);

        $this->session(['rules.create.uri' => 'http://localhost']);
        $data = [
            'rule_group_id'      => 1,
            'active'             => 1,
            'title'              => 'A',
            'trigger'            => 'store-journal',
            'description'        => 'D',
            'rule-trigger'       => [
                1 => 'from_account_starts',
            ],
            'rule-trigger-value' => [
                1 => 'B',
            ],
            'rule-action'        => [
                1 => 'set_category',
            ],
            'rule-action-value'  => [
                1 => 'C',
            ],
        ];
        $this->be($this->user());
        $response = $this->post(route('rules.store', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     *
     * @covers \FireflyIII\Http\Controllers\RuleController::testTriggers
     * @covers \FireflyIII\Http\Controllers\RuleController::getValidTriggerList
     */
    public function testTestTriggers()
    {
        $data = [
            'rule-trigger'       => ['description_is'],
            'rule-trigger-value' => ['Bla bla'],
            'rule-trigger-stop'  => ['1'],
        ];

        // mock stuff
        $matcher      = $this->mock(TransactionMatcher::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $matcher->shouldReceive('setLimit')->withArgs([10])->andReturnSelf()->once();
        $matcher->shouldReceive('setRange')->withArgs([200])->andReturnSelf()->once();
        $matcher->shouldReceive('setTriggers')->andReturnSelf()->once();
        $matcher->shouldReceive('findTransactionsByTriggers')->andReturn(new Collection);

        $this->be($this->user());
        $uri      = route('rules.test-triggers') . '?' . http_build_query($data);
        $response = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * This actually hits an error and not the actually code but OK.
     *
     * @covers \FireflyIII\Http\Controllers\RuleController::testTriggers
     * @covers \FireflyIII\Http\Controllers\RuleController::getValidTriggerList
     */
    public function testTestTriggersError()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $uri      = route('rules.test-triggers');
        $response = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::testTriggers
     * @covers \FireflyIII\Http\Controllers\RuleController::getValidTriggerList
     */
    public function testTestTriggersMax()
    {
        $data = [
            'rule-trigger'       => ['description_is'],
            'rule-trigger-value' => ['Bla bla'],
            'rule-trigger-stop'  => ['1'],
        ];
        $set  = factory(Transaction::class, 10)->make();

        // mock stuff
        $matcher      = $this->mock(TransactionMatcher::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $matcher->shouldReceive('setLimit')->withArgs([10])->andReturnSelf()->once();
        $matcher->shouldReceive('setRange')->withArgs([200])->andReturnSelf()->once();
        $matcher->shouldReceive('setTriggers')->andReturnSelf()->once();
        $matcher->shouldReceive('findTransactionsByTriggers')->andReturn($set);

        $this->be($this->user());
        $uri      = route('rules.test-triggers') . '?' . http_build_query($data);
        $response = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::up
     */
    public function testUp()
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('moveUp');

        $this->be($this->user());
        $response = $this->get(route('rules.up', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleController::update
     */
    public function testUpdate()
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $ruleGroupRepos->shouldReceive('find')->andReturn(new RuleGroup)->once();
        $repository->shouldReceive('update');

        $data = [
            'rule_group_id'      => 1,
            'title'              => 'Your first default rule',
            'trigger'            => 'store-journal',
            'active'             => 1,
            'description'        => 'This rule is an example. You can safely delete it.',
            'rule-trigger'       => [
                1 => 'description_is',
            ],
            'rule-trigger-value' => [
                1 => 'something',
            ],
            'rule-action'        => [
                1 => 'prepend_description',
            ],
            'rule-action-value'  => [
                1 => 'Bla bla',
            ],
        ];
        $this->session(['rules.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('rules.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
