<?php
/**
 * TransactionLinkControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Api\V1\Controllers;


use Carbon\Carbon;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Transformers\TransactionLinkTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class TransactionLinkControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionLinkControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
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
        $transformer  = $this->mock(TransactionLinkTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $journalRepos->shouldReceive('findNull')->andReturn($journal)->atLeast()->once();
        $repository->shouldReceive('storeLink')->once()->andReturn($journalLink);
        $repository->shouldReceive('findLink')->once()->andReturn(false);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post(route('api.v1.transaction_links.store'), $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * In this particular test the journal link request will fail.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testStoreExistingLink(): void
    {
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();


        $journalRepos->shouldReceive('findNull')->andReturn($journal);
        $repository->shouldReceive('findLink')->once()->andReturn(true);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post(route('api.v1.transaction_links.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertSee('Already have a link between inward and outward.');


        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * In this particular test the TransactionLinkRequest will report the failure.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testStoreInvalidInward(): void
    {
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->once()->withArgs([1])->andReturn(null);
        $journalRepos->shouldReceive('findNull')->once()->withArgs([2])->andReturn(null);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post(route('api.v1.transaction_links.store'), $data, ['Accept' => 'application/json']);
        $response->assertSee('Invalid inward ID.'); // the creation moment.
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * In this particular test the TransactionLinkRequest will report the failure.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testStoreInvalidOutward(): void
    {
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->once()->withArgs([1])->andReturn($journal);
        $journalRepos->shouldReceive('findNull')->once()->withArgs([2])->andReturn(null);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post(route('api.v1.transaction_links.store'), $data, ['Accept' => 'application/json']);
        $response->assertSee('Invalid outward ID.');
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testStoreNoJournal(): void
    {
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->twice()->withArgs([1])->andReturn($journal, null);
        $journalRepos->shouldReceive('findNull')->twice()->withArgs([2])->andReturn($journal, null);
        $repository->shouldReceive('findLink')->once()->andReturn(false);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        Log::warning('The following error is part of a test.');
        $response = $this->post(route('api.v1.transaction_links.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('200024'); // the creation moment.
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testStoreWithNull(): void
    {
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock stuff:
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->andReturn(null);


        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->post(route('api.v1.transaction_links.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertSee('Invalid inward ID.'); // error message
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $transformer  = $this->mock(TransactionLinkTransformer::class);

        $journalLink                        = TransactionJournalLink::first();
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->andReturn($journal);
        $repository->shouldReceive('updateLink')->once()->andReturn($journalLink);
        $repository->shouldReceive('findLink')->once()->andReturn(false);

        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->put(route('api.v1.transaction_links.update', $journalLink->id), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testUpdateNoJournal(): void
    {
        // mock repositories
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalLink                        = TransactionJournalLink::first();
        $journal                            = $this->user()->transactionJournals()->find(1);
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->twice()->withArgs([1])->andReturn($journal, null);
        $journalRepos->shouldReceive('findNull')->twice()->withArgs([2])->andReturn($journal, null);
        $repository->shouldReceive('findLink')->once()->andReturn(false);

        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        Log::warning('The following error is part of a test.');
        $response = $this->put(route('api.v1.transaction_links.update', $journalLink->id), $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('200024'); // the creation moment.
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionLinkController
     * @covers \FireflyIII\Api\V1\Requests\TransactionLinkRequest
     */
    public function testUpdateWithNull(): void
    {
        // mock repositories
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalLink                        = TransactionJournalLink::first();
        $transaction                        = Transaction::first();
        $transaction->date                  = new Carbon;
        $transaction->transaction_type_type = 'Withdrawal';


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        $journalRepos->shouldReceive('findNull')->andReturn(null);

        // data to submit
        $data = [
            'link_type_id' => '1',
            'inward_id'    => '1',
            'outward_id'   => '2',
            'notes'        => 'Some notes',
        ];

        // test API
        $response = $this->put(route('api.v1.transaction_links.update', $journalLink->id), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertSee('Invalid inward ID.'); // the creation moment.
        $response->assertHeader('Content-Type', 'application/json');
    }
}
