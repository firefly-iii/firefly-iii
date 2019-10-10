<?php
/**
 * PreferencesControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class PreferencesControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreferencesControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\PreferencesController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_preferences_index');
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();

        // mock get preferences:

        $frontPage       = new Preference;
        $frontPage->data = [];
        Preferences::shouldReceive('get')->withArgs(['frontPageAccounts', []])->andReturn($frontPage)->atLeast()->once();

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $pref       = new Preference;
        $pref->data = 0;
        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', 0])->atLeast()->once()->andReturn($pref);

        $pref       = new Preference;
        $pref->data = '01-01';
        Preferences::shouldReceive('get')->withArgs(['fiscalYearStart', '01-01'])->atLeast()->once()->andReturn($pref);

        $pref       = new Preference;
        $pref->data = [];
        Preferences::shouldReceive('get')->withArgs(['transaction_journal_optional_fields', []])->atLeast()->once()->andReturn($pref);


        $this->be($this->user());
        $response = $this->get(route('preferences.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController
     */
    public function testPostIndex(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->andReturn(false);

        $data = [
            'fiscalYearStart'       => '2016-01-01',
            'frontPageAccounts'     => [1],
            'viewRange'             => '1M',
            'customFiscalYear'      => 0,
            'showDepositsFrontpage' => 0,
            'listPageSize'          => 100,
            'language'              => 'en_US',
            'tj'                    => [],
        ];

        Preferences::shouldReceive('set')->withArgs(['frontPageAccounts', [1]])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['viewRange', '1M'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['customFiscalYear', false])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['fiscalYearStart', '01-01'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['listPageSize', 100])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['listPageSize', 50])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['language', 'en_US'])->atLeast()->once();
        Preferences::shouldReceive('set')->withArgs(['transaction_journal_optional_fields', Mockery::any()])->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('preferences.update'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('preferences.index'));
    }
}
