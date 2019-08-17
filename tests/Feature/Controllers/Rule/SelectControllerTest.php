<?php
/**
 * SelectControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace tests\Feature\Controllers\Rule;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use FireflyIII\TransactionRules\TransactionMatcher;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;


/**
 * Class SelectControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SelectControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Rule\SelectController
     */
    public function testExecute(): void
    {
        $account      = $this->user()->accounts()->find(1);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $this->mockDefaultSession();
        $collector  = $this->mock(GroupCollectorInterface::class);
        $ruleEngine = $this->mock(RuleEngine::class);


        $this->session(['first' => new Carbon('2010-01-01')]);
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        // new mocks for ruleEngine
        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->atLeast()->once();

        $collector->shouldReceive('setAccounts')->atLeast()->once();
        $collector->shouldReceive('setRange')->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([['x']]);


        $data = [
            'accounts'   => [1],
            'start_date' => '2017-01-01',
            'end_date'   => '2017-01-02',
        ];

        $this->be($this->user());
        $response = $this->post(route('rules.execute', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Rule\SelectController
     */
    public function testSelectTransactions(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $this->mockDefaultSession();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('rules.select-transactions', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Rule\SelectController
     */
    public function testTestTriggers(): void
    {
        $data = [
            'triggers' => [
                'name'            => 'description',
                'value'           => 'Bla bla',
                'stop_processing' => 1,
            ],
        ];

        // mock stuff
        $matcher      = $this->mock(TransactionMatcher::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mockDefaultSession();

        $matcher->shouldReceive('setStrict')->once()->withArgs([false])->andReturnSelf();
        $matcher->shouldReceive('setTriggeredLimit')->withArgs([10])->andReturnSelf()->once();
        $matcher->shouldReceive('setSearchLimit')->withArgs([200])->andReturnSelf()->once();
        $matcher->shouldReceive('setTriggers')->andReturnSelf()->once();
        $matcher->shouldReceive('findTransactionsByTriggers')->andReturn([]);

        $this->be($this->user());
        $uri      = route('rules.test-triggers') . '?' . http_build_query($data);
        $response = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Rule\SelectController
     */
    public function testTestTriggersByRule(): void
    {
        $matcher      = $this->mock(TransactionMatcher::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mockDefaultSession();

        $matcher->shouldReceive('setTriggeredLimit')->withArgs([10])->andReturnSelf()->once();
        $matcher->shouldReceive('setSearchLimit')->withArgs([200])->andReturnSelf()->once();
        $matcher->shouldReceive('setRule')->andReturnSelf()->once();
        $matcher->shouldReceive('findTransactionsByRule')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('rules.test-triggers-rule', [1]));
        $response->assertStatus(200);

    }

    /**
     * This actually hits an error and not the actually code but OK.
     *
     * @covers \FireflyIII\Http\Controllers\Rule\SelectController
     */
    public function testTestTriggersError(): void
    {
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mockDefaultSession();

        $this->be($this->user());
        $uri      = route('rules.test-triggers');
        $response = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Rule\SelectController
     */
    public function testTestTriggersMax(): void
    {
        $data = [
            'triggers' => [
                'name'            => 'description',
                'value'           => 'Bla bla',
                'stop_processing' => 1,
            ],
        ];

        // mock stuff
        $matcher      = $this->mock(TransactionMatcher::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);


        $matcher->shouldReceive('setStrict')->once()->withArgs([false]);

        $matcher->shouldReceive('setTriggeredLimit')->withArgs([10])->andReturnSelf()->once();
        $matcher->shouldReceive('setSearchLimit')->withArgs([200])->andReturnSelf()->once();
        $matcher->shouldReceive('setTriggers')->andReturnSelf()->once();
        $matcher->shouldReceive('findTransactionsByTriggers')->andReturn([]);

        $this->be($this->user());
        $uri      = route('rules.test-triggers') . '?' . http_build_query($data);
        $response = $this->get($uri);
        $response->assertStatus(200);
    }
}
