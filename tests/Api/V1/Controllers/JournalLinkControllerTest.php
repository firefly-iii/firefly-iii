<?php
/**
 * JournalLinkControllerTest.php
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

namespace Tests\Api\V1\Controllers;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class JournalLinkControllerTest
 */
class JournalLinkControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::debug(sprintf('Now in %s.', \get_class($this)));

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroyLink')->once()->andReturn(true);

        // get a link
        /** @var TransactionJournalLink $journalLink */
        $journalLink = TransactionJournalLink::first();

        // call API
        $response = $this->delete('/api/v1/journal_links/' . $journalLink->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     */
    public function testIndex(): void
    {
        $journalLinks                       = TransactionJournalLink::get();
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';
        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByName')->once()->andReturn(null);
        $repository->shouldReceive('getJournalLinks')->once()->andReturn($journalLinks);

        $journalRepos->shouldReceive('setUser')->once();

        $collector->shouldReceive('setUser')->withAnyArgs();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));

        // call API
        $response = $this->get('/api/v1/journal_links');
        $response->assertStatus(200);
        $response->assertSee($journalLinks->first()->id);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     */
    public function testShow(): void
    {
        $journalLink                        = TransactionJournalLink::first();
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('setUser')->once();
        $collector->shouldReceive('setUser')->withAnyArgs();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));

        // call API
        $response = $this->get('/api/v1/journal_links/' . $journalLink->id);
        $response->assertStatus(200);
        $response->assertSee($journalLink->id);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     * @covers \FireflyIII\Api\V1\Requests\JournalLinkRequest
     */
    public function testStore(): void
    {
        $journalLink                        = TransactionJournalLink::first();
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('setUser')->once();
        $collector->shouldReceive('setUser')->withAnyArgs();

        $collector->shouldReceive('setUser')->withAnyArgs();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));

        $journalRepos->shouldReceive('findNull')->andReturn($journal);
        $repository->shouldReceive('storeLink')->once()->andReturn($journalLink);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post('/api/v1/journal_links', $data);
        $response->assertStatus(200);
        $response->assertSee($journalLink->created_at->toAtomString()); // the creation moment.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     * @covers \FireflyIII\Api\V1\Requests\JournalLinkRequest
     */
    public function testStoreWithNull(): void
    {
        $journalLink                        = TransactionJournalLink::first();
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('setUser')->once();
        $collector->shouldReceive('setUser')->withAnyArgs();

        $collector->shouldReceive('setUser')->withAnyArgs();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));

        $journalRepos->shouldReceive('findNull')->andReturn(null);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post('/api/v1/journal_links', $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('Source or destination is NULL.'); // error message
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     * @covers \FireflyIII\Api\V1\Requests\JournalLinkRequest
     */
    public function testUpdate(): void
    {

        // mock repositories
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        $journalLink                        = TransactionJournalLink::first();
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';


        // mock calls:
        $repository->shouldReceive('setUser');
        $journalRepos->shouldReceive('setUser')->once();
        $collector->shouldReceive('setUser')->withAnyArgs();

        $collector->shouldReceive('setUser')->withAnyArgs();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));

        $journalRepos->shouldReceive('findNull')->andReturn($journal);
        $repository->shouldReceive('updateLink')->once()->andReturn($journalLink);

        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->put('/api/v1/journal_links/' . $journalLink->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertSee($journalLink->created_at->toAtomString()); // the creation moment.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\JournalLinkController
     * @covers \FireflyIII\Api\V1\Requests\JournalLinkRequest
     */
    public function testUpdateWithNull(): void
    {

        // mock repositories
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);

        $journalLink                        = TransactionJournalLink::first();
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';


        // mock calls:
        $repository->shouldReceive('setUser');
        $journalRepos->shouldReceive('setUser')->once();
        $collector->shouldReceive('setUser')->withAnyArgs();

        $collector->shouldReceive('setUser')->withAnyArgs();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));

        $journalRepos->shouldReceive('findNull')->andReturn(null);

        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->put('/api/v1/journal_links/' . $journalLink->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('Source or destination is NULL.'); // the creation moment.
        $response->assertHeader('Content-Type', 'application/json');
    }
}
