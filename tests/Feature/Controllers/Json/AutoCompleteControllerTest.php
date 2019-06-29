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


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
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


}
