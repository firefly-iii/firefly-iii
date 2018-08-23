<?php
/**
 * PiggyBankControllerTest.php
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

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class PiggyBankControllerTest
 */
class PiggyBankControllerTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Destroy piggy bank over API
     *
     * @covers \FireflyIII\Api\V1\Controllers\PiggyBankController
     */
    public function testDelete(): void
    {   // mock stuff:
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get piggy bank:
        $piggyBank = $this->user()->piggyBanks()->first();

        // call API
        $response = $this->delete('/api/v1/piggy_banks/' . $piggyBank->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PiggyBankController
     */
    public function testIndex(): void
    {
        // create stuff
        $piggies       = factory(PiggyBank::class, 10)->create();
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getPiggyBanks')->withAnyArgs()->andReturn($piggies)->once();
        $repository->shouldReceive('getCurrentAmount')->andReturn('12');
        $repository->shouldReceive('getSuggestedMonthlyAmount')->andReturn('12');

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        $currencyRepos->shouldReceive('setUser');
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first());

        // test API
        $response = $this->get('/api/v1/piggy_banks');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PiggyBankController
     */
    public function testShow(): void
    {
        // create stuff
        $piggy = $this->user()->piggyBanks()->first();

        // mock stuff:
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('getCurrentAmount')->andReturn('12');
        $repository->shouldReceive('getSuggestedMonthlyAmount')->andReturn('12');

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        $currencyRepos->shouldReceive('setUser');
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first());

        // test API
        $response = $this->get('/api/v1/piggy_banks/' . $piggy->id);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'piggy_banks', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PiggyBankController
     * @covers \FireflyIII\Api\V1\Requests\PiggyBankRequest
     */
    public function testStore(): void
    {
        // create stuff
        $piggy = $this->user()->piggyBanks()->first();

        // mock stuff:
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->once()->andReturn($piggy);

        $repository->shouldReceive('getCurrentAmount')->andReturn('12')->once();
        $repository->shouldReceive('getSuggestedMonthlyAmount')->andReturn('12')->once();

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->once();

        $currencyRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first())->once();

        $data = [
            'name'          => 'New piggy #' . random_int(1, 100000),
            'account_id'    => 1,
            'target_amount' => '100',
        ];

        // test API
        $response = $this->post('/api/v1/piggy_banks/', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'piggy_banks', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PiggyBankController
     * @covers \FireflyIII\Api\V1\Requests\PiggyBankRequest
     */
    public function testStoreNull(): void
    {
        // mock stuff:
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn(null)->once();


        $data = [
            'name'          => 'New piggy #' . random_int(1, 100000),
            'account_id'    => 1,
            'target_amount' => '100',
        ];

        // test API
        $response = $this->post('/api/v1/piggy_banks/', $data, ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertSee('Could not store new piggy bank.');

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PiggyBankController
     * @covers \FireflyIII\Api\V1\Requests\PiggyBankRequest
     */
    public function testUpdate(): void
    {
        // create stuff
        $piggy = $this->user()->piggyBanks()->first();

        // mock stuff:
        $repository    = $this->mock(PiggyBankRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('update')->once()->andReturn($piggy);

        $repository->shouldReceive('getCurrentAmount')->andReturn('12');
        $repository->shouldReceive('getSuggestedMonthlyAmount')->andReturn('12');

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        $currencyRepos->shouldReceive('setUser');
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::first());

        $data = [
            'name' => 'new pigy bank ' . random_int(1, 10000),
            'account_id'    => 1,
            'target_amount' => '100',
        ];

        // test API
        $response = $this->put('/api/v1/piggy_banks/' . $piggy->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'piggy_banks', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


}