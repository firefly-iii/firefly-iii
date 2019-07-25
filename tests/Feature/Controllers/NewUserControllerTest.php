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

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Preferences;
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
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
        $this->mockDefaultSession();
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

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
        $this->mockDefaultSession();
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $euro          = $this->getEuro();
        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn($euro);
        $currencyRepos->shouldReceive('enable')->once();

        Preferences::shouldReceive('set')->withArgs(['language', 'en_US'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['transaction_journal_optional_fields', Mockery::any()])->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

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
        $euro = $this->getEuro();
        $this->mockDefaultSession();
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn(null);
        $currencyRepos->shouldReceive('findByCodeNull')->withArgs(['EUR'])->andReturn($euro)->once();
        $currencyRepos->shouldReceive('enable')->once();

        Preferences::shouldReceive('set')->withArgs(['language', 'en_US'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['transaction_journal_optional_fields', Mockery::any()])->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

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
        $this->mockDefaultSession();
        $euro = $this->getEuro();
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn($euro);
        $currencyRepos->shouldReceive('enable')->once();

        Preferences::shouldReceive('set')->withArgs(['language', 'en_US'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['transaction_journal_optional_fields', Mockery::any()])->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

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
