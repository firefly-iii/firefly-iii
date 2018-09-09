<?php
/**
 * DeleteControllerTest.php
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

namespace Tests\Feature\Controllers\Account;


use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class DeleteControllerTest
 */
class DeleteControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\DeleteController
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testDelete(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->withArgs([[AccountType::ASSET]])->andReturn(new Collection);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 3)->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.delete', [$account->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\DeleteController
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testDestroy(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findNull')->withArgs([0])->once()->andReturn(null);
        $repository->shouldReceive('destroy')->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['accounts.delete.uri' => 'http://localhost/accounts/show/1']);
        $account = $this->user()->accounts()->where('account_type_id', 3)->whereNull('deleted_at')->first();

        $this->be($this->user());
        $response = $this->post(route('accounts.destroy', [$account->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }


}
