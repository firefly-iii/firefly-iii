<?php
/**
 * ExecutionControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests\Feature\Controllers\RuleGroup;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Jobs\ExecuteRuleGroupOnExistingTransactions;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ExecutionControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExecutionControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroup\ExecutionController
     */
    public function testExecute(): void
    {
        $this->mockDefaultSession();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $collector  = $this->mock(GroupCollectorInterface::class);
        $ruleEngine = $this->mock(RuleEngine::class);

        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection);

        // new mocks for ruleEngine
        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->atLeast()->once();

        $collector->shouldReceive('setAccounts')->atLeast()->once();
        $collector->shouldReceive('setRange')->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([['x']]);

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
     * @covers \FireflyIII\Http\Controllers\RuleGroup\ExecutionController
     */
    public function testSelectTransactions(): void
    {
        $this->mockDefaultSession();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('rule-groups.select-transactions', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

}