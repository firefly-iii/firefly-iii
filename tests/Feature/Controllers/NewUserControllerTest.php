<?php
/**
 * NewUserControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class NewUserControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NewUserControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\NewUserController
     */
    public function testIndex(): void
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(0);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);


        $this->be($this->emptyUser());
        $response = $this->get(route('new-user.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController
     */
    public function testIndexExisting(): void
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(1);

        $this->be($this->user());
        $response = $this->get(route('new-user.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController
     */
    public function testSubmit(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $currencyRepos->shouldReceive('enable')->once();

        $data = [
            'bank_name'                       => 'New bank',
            'savings_balance'                 => '1000',
            'bank_balance'                    => '100',
            'language'                        => 'en_US',
            'amount_currency_id_bank_balance' => 1,
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController
     */
    public function testSubmitNull(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn(null);
        $currencyRepos->shouldReceive('findByCodeNull')->withArgs(['EUR'])->andReturn(TransactionCurrency::find(2))->once();
        $currencyRepos->shouldReceive('enable')->once();

        $data = [
            'bank_name'                       => 'New bank',
            'savings_balance'                 => '1000',
            'bank_balance'                    => '100',
            'language'                        => 'en_US',
            'amount_currency_id_bank_balance' => 1,
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController
     */
    public function testSubmitSingle(): void
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $currencyRepos->shouldReceive('enable')->once();

        $data = [
            'bank_name'                       => 'New bank',
            'bank_balance'                    => '100',
            'amount_currency_id_bank_balance' => 1,
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
