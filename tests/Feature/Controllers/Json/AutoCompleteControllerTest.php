<?php
/**
 * AutoCompleteControllerTest.php
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

namespace Tests\Feature\Controllers\Json;


use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class AutoCompleteControllerTest
 */
class AutoCompleteControllerTest extends TestCase
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
     * Request a list of asset accounts
     *
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAccounts(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->getRandomAsset();
        $euro         = $this->getEuro();

        $accountRepos->shouldReceive('searchAccount')->atLeast()->once()->andReturn(new Collection([$account]));
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $this->mockDefaultSession();

        $this->be($this->user());
        $httpQuery = http_build_query(['types' => AccountType::ASSET]);
        $response  = $this->get(route('json.autocomplete.accounts') . '?' . $httpQuery);
        $response->assertStatus(200);
        $response->assertSee($account->name);
    }


    /**
     * Request a list of revenue accounts
     *
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testRevenueAccounts(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->getRandomAsset();

        $accountRepos->shouldReceive('searchAccount')->atLeast()->once()->andReturn(new Collection([$account]));
        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('json.autocomplete.revenue-accounts'));
        $response->assertStatus(200);
        $response->assertSee($account->name);
    }

    /**
     * Request a list of expense accounts
     *
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testExpenseAccounts(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->getRandomAsset();

        $accountRepos->shouldReceive('searchAccount')->atLeast()->once()->andReturn(new Collection([$account]));
        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('json.autocomplete.expense-accounts'));
        $response->assertStatus(200);
        $response->assertSee($account->name);
    }


    /**
     * Request a list of expense accounts
     *
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAllJournals(): void
    {
        $journalRepos = $this->mockDefaultSession();
        $journal      = $this->getRandomWithdrawalAsArray();

        $journalRepos->shouldReceive('searchJournalDescriptions')->atLeast()->once()->andReturn(new Collection([$journal]));


        $this->be($this->user());
        $response = $this->get(route('json.autocomplete.all-journals'));
        $response->assertStatus(200);
        $response->assertSee($journal['description']);
    }

    /**
     * Request a list of expense accounts
     *
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAllJournalsWithId(): void
    {
        $journalRepos = $this->mockDefaultSession();
        $journal      = $this->getRandomWithdrawalAsArray();

        $journalRepos->shouldReceive('searchJournalDescriptions')->atLeast()->once()->andReturn(new Collection([$journal]));


        $this->be($this->user());
        $response = $this->get(route('json.autocomplete.all-journals-with-id'));
        $response->assertStatus(200);
        $response->assertSee($journal['description']);
        $response->assertSee($journal['id']);
    }


    /**
     * Request a list of expense accounts
     *
     * @covers \FireflyIII\Http\Controllers\Json\AutoCompleteController
     */
    public function testAllJournalsWithIdNumeric(): void
    {
        $journalRepos  = $this->mockDefaultSession();
        $journal       = $this->getRandomWithdrawalAsArray();
        $journalObject = $this->getRandomWithdrawal();

        $journalRepos->shouldReceive('searchJournalDescriptions')->atLeast()->once()->andReturn(new Collection([$journal]));
        $journalRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($journalObject);


        $this->be($this->user());
        $response = $this->get(route('json.autocomplete.all-journals-with-id') . '?search=' . $journal['id']);
        $response->assertStatus(200);
        $response->assertSee($journal['description']);
        $response->assertSee($journal['id']);
    }


}
