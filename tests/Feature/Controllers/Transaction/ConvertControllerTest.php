<?php
/**
 * ConvertControllerTest.php
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

use Amount;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 * Class ConvertControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConvertControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexDepositTransfer(): void
    {
        // mock stuff:
        $journalRepos  = $this->mockDefaultSession();
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $groupRepos    = $this->mock(TransactionGroupRepositoryInterface::class);
        $transformer   = $this->mock(TransactionGroupTransformer::class);

        $revenue = $this->getRandomRevenue();
        $deposit = $this->getRandomDepositGroup();
        $euro    = $this->getEuro();
        $asset   = $this->getRandomAsset();
        $loan    = $this->getRandomLoan();
        $expense = $this->getRandomExpense();

        Steam::shouldReceive('balance')->atLeast()->once()->andReturn('100');

        // mock calls:
        $transformer->shouldReceive('transformObject')->atLeast()->once()->andReturn([]);

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::REVENUE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])
                     ->andReturn(new Collection([$revenue]));

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::EXPENSE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])
                     ->andReturn(new Collection([$expense]));

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])
                     ->andReturn(new Collection([$loan]));

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::ASSET]])
                     ->andReturn(new Collection([$asset]));


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'account_role'])->andReturn('', 'defaultAsset');
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        //        $journalRepos->shouldReceive('firstNull')->andReturn($deposit);
        //        $journalRepos->shouldReceive('getJournalTotal')->andReturn('1')->once();
        //        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection)->once();
        //        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection)->once();

        //        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency)->times(2);
        Amount::shouldReceive('formatAnything')->andReturn('0')->atLeast()->once();

        $this->be($this->user());

        $response = $this->get(route('transactions.convert.index', ['transfer', $deposit->id]));
        $response->assertStatus(200);
        $response->assertSee('Convert a deposit into a transfer');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testIndexSameType(): void
    {
        // mock stuff:
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(TransactionGroupTransformer::class);

        $revenue = $this->getRandomRevenue();
        $deposit = $this->getRandomDepositGroup();
        $euro    = $this->getEuro();
        $asset   = $this->getRandomAsset();
        $loan    = $this->getRandomLoan();
        $expense = $this->getRandomExpense();

        Steam::shouldReceive('balance')->atLeast()->once()->andReturn('100');

        // mock calls:
        $transformer->shouldReceive('transformObject')->atLeast()->once()->andReturn([]);

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::REVENUE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])
                     ->andReturn(new Collection([$revenue]));

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::EXPENSE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])
                     ->andReturn(new Collection([$expense]));

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])
                     ->andReturn(new Collection([$loan]));

        $accountRepos->shouldReceive('getActiveAccountsByType')
                     ->atLeast()->once()->withArgs([[AccountType::ASSET]])
                     ->andReturn(new Collection([$asset]));


        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'account_role'])->andReturn('', 'defaultAsset');
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        Amount::shouldReceive('formatAnything')->andReturn('0')->atLeast()->once();

        $this->be($this->user());

        $response = $this->get(route('transactions.convert.index', ['deposit', $deposit->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexDepositTransfer(): void
    {
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(RuleGroupRepositoryInterface::class);
        $validator = $this->mock(AccountValidator::class);
        $deposit   = $this->getRandomDepositGroup();

        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['Transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);


        $data = ['source_account_id' => 1];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index.post', ['transfer', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('transactions.show', [$deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexBadSource(): void
    {
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(RuleGroupRepositoryInterface::class);
        $validator = $this->mock(AccountValidator::class);
        $deposit   = $this->getRandomDepositGroup();

        // first journal:
        $journal = $deposit->transactionJournals()->first();

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['Transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(false);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);


        $data = ['source_account_id' => 1];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index.post', ['transfer', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error', sprintf('Source information is invalid for transaction #%d.', $journal->id));
        $response->assertRedirect(route('transactions.convert.index', ['transfer', $deposit->id]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\ConvertController
     */
    public function testPostIndexBadDestination(): void
    {
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(RuleGroupRepositoryInterface::class);
        $validator = $this->mock(AccountValidator::class);
        $deposit   = $this->getRandomDepositGroup();

        // first journal:
        $journal = $deposit->transactionJournals()->first();

        $validator->shouldReceive('setUser')->atLeast()->once();
        $validator->shouldReceive('setTransactionType')->atLeast()->once()->withArgs(['Transfer']);
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(false);


        $data = ['source_account_id' => 1];
        $this->be($this->user());
        $response = $this->post(route('transactions.convert.index.post', ['transfer', $deposit->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error', sprintf('Destination information is invalid for transaction #%d.', $journal->id));
        $response->assertRedirect(route('transactions.convert.index', ['transfer', $deposit->id]));
    }
}
