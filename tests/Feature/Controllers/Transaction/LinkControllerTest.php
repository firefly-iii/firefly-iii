<?php
/**
 * LinkControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Transaction;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class LinkControllerTest
 */
class LinkControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController
     */
    public function testDelete(): void
    {
        $this->mockDefaultSession();
        $this->mock(LinkTypeRepositoryInterface::class);
        $link      = $this->getRandomLink();
        $userRepos = $this->mock(UserRepositoryInterface::class);


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('transactions.link.delete', [$link->id]));
        $response->assertStatus(200);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController
     */
    public function testModal(): void
    {
        $this->mockDefaultSession();
        $journal   = $this->getRandomWithdrawal();
        $linkRepos = $this->mock(LinkTypeRepositoryInterface::class);


        $linkRepos->shouldReceive('get')->atLeast()->once()->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.link.modal', [$journal->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController
     */
    public function testDestroy(): void
    {
        $this->mockDefaultSession();
        $link       = $this->getRandomLink();
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        Preferences::shouldReceive('mark')->once();


        $repository->shouldReceive('destroyLink')->atLeast()->once();
        $this->be($this->user());
        $this->session(['journal_links.delete.uri' => 'http://localhost/']);

        $response = $this->post(route('transactions.link.destroy', [$link->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');

    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\LinkController
     * @covers       \FireflyIII\Http\Requests\JournalLinkRequest
     */
    public function testStore(): void
    {
        $this->mockDefaultSession();
        $withdrawal   = $this->getRandomWithdrawal();
        $deposit      = $this->getRandomDeposit();
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mockDefaultSession();

        $data = [
            'opposing'  => $deposit->id,
            'link_type' => '1_inward',
        ];

        //$journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('findNull')->andReturn($deposit)->atLeast()->once();
        $repository->shouldReceive('findLink')->andReturn(false)->atLeast()->once();
        $repository->shouldReceive('storeLink')->andReturn(new TransactionJournalLink)->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [$withdrawal->id]), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('transactions.show', [$withdrawal->transaction_group_id]));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\LinkController
     * @covers       \FireflyIII\Http\Requests\JournalLinkRequest
     */
    public function testStoreAlreadyLinked(): void
    {
        $this->mockDefaultSession();
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mockDefaultSession();
        $link         = $this->getRandomLink();

        $data = [
            'opposing'  => $link->source_id,
            'link_type' => '1_inward',
        ];

        $journalRepos->shouldReceive('findNull')->andReturn(new TransactionJournal)->atLeast()->once();
        $repository->shouldReceive('findLink')->andReturn(true)->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [$link->destination_id]), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('transactions.show', [$link->destination_id]));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\LinkController
     * @covers       \FireflyIII\Http\Requests\JournalLinkRequest
     */
    public function testStoreInvalid(): void
    {
        $this->mockDefaultSession();
        $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mockDefaultSession();
        $withdrawal   = $this->getRandomWithdrawal();

        $data = [
            'opposing'  => 0,
            'link_type' => '1_inward',
        ];

        $journalRepos->shouldReceive('findNull')->andReturn(null)->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [$withdrawal->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\LinkController
     * @covers       \FireflyIII\Http\Requests\JournalLinkRequest
     */
    public function testStoreSame(): void
    {
        $this->mockDefaultSession();
        $withdrawal   = $this->getRandomWithdrawal();
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mockDefaultSession();

        $data = [
            'link_other' => $withdrawal->id,
            'link_type'  => '1_inward',
        ];
        $journalRepos->shouldReceive('findNull')->andReturn($withdrawal)->atLeast()->once();
        $repository->shouldReceive('findLink')->andReturn(false)->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [$withdrawal->id]), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('transactions.show', [$withdrawal->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController
     */
    public function testSwitchLink(): void
    {
        $this->mockDefaultSession();
        $link       = $this->getRandomLink();
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);


        $repository->shouldReceive('switchLink')->andReturn(false);
        $this->be($this->user());
        $response = $this->get(route('transactions.link.switch', [$link->id]));


        $response->assertStatus(302);
    }
}
