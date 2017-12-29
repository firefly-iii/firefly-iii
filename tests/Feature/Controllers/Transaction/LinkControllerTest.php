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
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Tests\TestCase;


/**
 * Class LinkControllerTest
 */
class LinkControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::__construct
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.link.delete', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::destroy
     */
    public function testDestroy()
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $repository->shouldReceive('destroyLink');
        $this->be($this->user());

        $this->session(['journal_links.delete.uri' => 'http://localhost/']);

        $response = $this->post(route('transactions.link.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::store
     */
    public function testStore()
    {
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $data         = [
            'link_other' => 8,
            'link_type'  => '1_inward',
        ];

        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('find')->andReturn(new TransactionJournal);
        $repository->shouldReceive('findLink')->andReturn(false);
        $repository->shouldReceive('storeLink')->andReturn(new TransactionJournalLink);

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [1]), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('transactions.show', [1]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::store
     */
    public function testStoreAlreadyLinked()
    {
        $repository   = $this->mock(LinkTypeRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $data         = [
            'link_other' => 8,
            'link_type'  => '1_inward',
        ];

        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('find')->andReturn(new TransactionJournal);
        $repository->shouldReceive('findLink')->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [1]), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('transactions.show', [1]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::store
     */
    public function testStoreInvalid()
    {
        $data = [
            'link_other' => 0,
            'link_type'  => '1_inward',
        ];

        $this->be($this->user());
        $response = $this->post(route('transactions.link.store', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('transactions.show', [1]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\LinkController::switchLink
     */
    public function testSwitchLink()
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $repository->shouldReceive('switchLink')->andReturn(false);
        $this->be($this->user());
        $response = $this->get(route('transactions.link.switch', [1]));


        $response->assertStatus(302);
    }
}