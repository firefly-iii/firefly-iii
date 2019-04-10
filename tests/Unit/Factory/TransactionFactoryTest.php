<?php
/**
 * TransactionFactoryTest.php
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

namespace Tests\Unit\Factory;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\NullArrayObject;
use Log;
use Tests\TestCase;

/**
 * Class TransactionFactoryTest
 */
class TransactionFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreateBasic(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $journal = $this->getRandomWithdrawal();
        $account = $this->getRandomAsset();
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $amount  = '10';

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();


        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);
        $transaction = $factory->create($account, $euro, $amount);

        $this->assertEquals($transaction->account_id, $account->id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreateNull(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $journal = $this->getRandomWithdrawal();
        $account = new Account;
        $euro    = TransactionCurrency::whereCode('EUR')->first();
        $amount  = '10';

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();


        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);
        $transaction = $factory->create($account, $euro, $amount);

        $this->assertNull($transaction);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreatePair(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // used objects
        $withdrawal = $this->getRandomWithdrawal();
        $asset      = $this->getRandomAsset();
        $expense    = $this->getRandomExpense();
        $currency   = TransactionCurrency::whereCode('EUR')->first();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->withArgs([1])->once()->andReturn($asset);
        $accountRepos->shouldReceive('findByName')->withArgs(['Some destination', [AccountType::EXPENSE]])->once()->andReturn($expense);


        $data = new NullArrayObject(
            [
                'source_id'        => 1,
                'destination_name' => 'Some destination',
                'amount'           => '20',
            ]
        );
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);
        $pairs = $factory->createPair($data, $currency, null);
        $first = $pairs->first();
        $this->assertCount(2, $pairs);
        $this->assertEquals('-20', $first->amount);
        $this->assertEquals($currency->id, $first->transaction_currency_id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreatePairForeign(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // used objects:
        $withdrawal = $this->getRandomWithdrawal();
        $expense    = $this->getRandomExpense();
        $asset      = $this->getRandomAsset();
        $currency   = TransactionCurrency::whereCode('EUR')->first();
        $foreign    = TransactionCurrency::whereCode('USD')->first();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->withArgs([1])->once()->andReturn($asset);
        $accountRepos->shouldReceive('findByName')->withArgs(['Some destination', [AccountType::EXPENSE]])->once()->andReturn($expense);


        $data = new NullArrayObject(
            [
                'source_id'        => 1,
                'destination_name' => 'Some destination',
                'amount'           => '20',
                'foreign_amount'   => '20',
            ]
        );
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);
        $pairs = $factory->createPair($data, $currency, $foreign);
        $first = $pairs->first();
        $this->assertCount(2, $pairs);
        $this->assertEquals('-20', $first->amount);
        $this->assertEquals($currency->id, $first->transaction_currency_id);
        $this->assertEquals($foreign->id, $first->foreign_currency_id);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Deposit and an Revenue account ID (this is OK).
     * Expected result: the same revenue account.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testDepositSourceAsseRevenueId(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $deposit = $this->getRandomDeposit();
        $revenue = $this->getRandomRevenue();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([$revenue->id])->andReturn($revenue);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($deposit);

        $result = $factory->getAccount('source', null, $revenue->id, null);
        $this->assertEquals($revenue->id, $result->id);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Deposit and nothing else (this is OK).
     * Expected result: a cash account
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testDepositSourceRevenueCash(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $deposit = $this->getRandomDeposit();
        $revenue = $this->getRandomRevenue();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getCashAccount')->once()->andReturn($revenue);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($deposit);

        $result = $factory->getAccount('source', null, null, null);
        $this->assertEquals($revenue->name, $result->name);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Deposit and an Revenue account name (this is OK).
     * Expected result: a new revenue account.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testDepositSourceRevenueNameNew(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $deposit = $this->getRandomDeposit();
        $name    = 'random rev name ' . random_int(1, 100000);
        $revenue = $this->getRandomRevenue();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->once()->withArgs([$name, [AccountType::REVENUE]])->andReturnNull();
        // system will automatically expand search:
        $accountRepos->shouldReceive('findByName')->once()->withArgs(
            [$name, [AccountType::REVENUE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE, AccountType::INITIAL_BALANCE,
                     AccountType::RECONCILIATION]]
        )->andReturnNull();

        // then store new account:
        $accountRepos->shouldReceive('store')->once()->withArgs(
            [[
                 'account_type_id' => null,
                 'accountType'     => AccountType::REVENUE,
                 'name'            => $name,
                 'active'          => true,
                 'iban'            => null,
             ]]
        )->andReturn($revenue);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($deposit);

        $result = $factory->getAccount('source', null, null, $name);
        $this->assertEquals($revenue->name, $result->name);
    }

    /**
     * @throws FireflyException
     */
    public function testDramaBasic(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $withdrawal = $this->getRandomWithdrawal();
        $source     = $withdrawal->transactions()->where('amount', '<', 0)->first();
        $dest       = $withdrawal->transactions()->where('amount', '>', 0)->first();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();


        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);
        $factory->makeDramaOverAccountTypes($source->account, $dest->account);
    }

    /**
     * @throws FireflyException
     */
    public function testDramaNotAllowed(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $withdrawal = $this->getRandomWithdrawal();

        // this is an asset account.
        $source = $withdrawal->transactions()->where('amount', '<', 0)->first();
        // so destiny cannot be also asset account
        $dest = $this->getRandomAsset();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();


        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);
        try {
            $factory->makeDramaOverAccountTypes($source->account, $dest);
        } catch (FireflyException $e) {
            $this->assertEquals(
                'Journal of type "Withdrawal" has a source account of type "Asset account" and cannot accept a "Asset account"-account as destination, but only accounts of: Expense account, Loan, Debt, Mortgage',
                $e->getMessage()
            );
        }
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetAmountBasic(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $amount = '10';
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        $result = $factory->getAmount($amount);
        $this->assertEquals($amount, $result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetAmountNull(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $amount       = '';
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        try {
            $factory->getAmount($amount);
        } catch (FireflyException $e) {
            $this->assertEquals('The amount cannot be an empty string: ""', $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetAmountZero(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $amount       = '0.0';
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        try {
            $factory->getAmount($amount);
        } catch (FireflyException $e) {
            $this->assertEquals('The amount seems to be zero: "0.0"', $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetForeignAmountBasic(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $amount       = '10';
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        $result = $factory->getForeignAmount($amount);
        $this->assertEquals($amount, $result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetForeignAmountEmpty(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $amount       = '';
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        $result = $factory->getForeignAmount($amount);
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetForeignAmountNull(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $amount       = null;
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        $result = $factory->getForeignAmount($amount);
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testGetForeignAmountZero(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $amount       = '0.0';
        // data used in calls.
        $journal = $this->getRandomWithdrawal();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($journal);

        $result = $factory->getForeignAmount($amount);
        $this->assertNull($result);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and an Asset account ID (this is OK).
     * Expected result: the same asset account.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceAssetId(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $asset      = $this->getRandomAsset();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([$asset->id])->andReturn($asset);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);

        $result = $factory->getAccount('source', null, $asset->id, null);
        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and an Asset account ID (this is OK).
     * Expected result: find won't return anything so we expect a big fat error.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceAssetIdNOK(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $asset      = $this->getRandomAsset();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([$asset->id])->andReturn($asset);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);

        try {
            $factory->getAccount('source', null, $asset->id, null);
        } catch (FireflyException $e) {
            $this->assertEquals('TransactionFactory: Cannot create asset account with ID #0 or name "(no name)".', $e->getMessage());
        }
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and an Asset account name (this is OK).
     * Expected result: the same asset account.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceAssetName(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $asset      = $this->getRandomAsset();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->once()->withArgs([$asset->name, [AccountType::ASSET]])->andReturn($asset);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);

        $result = $factory->getAccount('source', null, null, $asset->name);
        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and an Asset account name (this is OK).
     * Expected result: the same asset account.
     *
     * This will initially return NULL and then search again with all possible types.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceAssetName2(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $asset      = $this->getRandomAsset();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->once()->withArgs([$asset->name, [AccountType::ASSET]])->andReturnNull();
        $accountRepos->shouldReceive('findByName')->once()->withArgs(
            [$asset->name, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]]
        )->andReturn($asset);

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);

        $result = $factory->getAccount('source', null, null, $asset->name);
        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and an Asset account object (this is OK).
     * Expected result: the same asset account.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceAssetObj(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $asset      = $this->getRandomAsset();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);

        $result = $factory->getAccount('source', $asset, null, null);
        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and an Expense account object (this is not OK).
     * Expected result: big fat error because of missing data.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceAssetObjNOK(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $expense    = $this->getRandomExpense();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);
        try {
            $factory->getAccount('source', $expense, null, null);
        } catch (FireflyException $e) {
            $this->assertEquals('TransactionFactory: Cannot create asset account with ID #0 or name "(no name)".', $e->getMessage());
        }
    }

    /**
     * To cover everything, test several combinations.
     *
     * For the source account, submit a Withdrawal and Loan account object (this is OK).
     * Expected result: the same loan account.
     *
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testWithdrawalSourceLoanObj(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        // mock classes
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // data used in calls.
        $withdrawal = $this->getRandomWithdrawal();
        $loan       = $this->getRandomLoan();

        // mock calls.
        $accountRepos->shouldReceive('setUser')->once();

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user());
        $factory->setJournal($withdrawal);

        $result = $factory->getAccount('source', $loan, null, null);
        $this->assertEquals($loan->id, $result->id);
    }


}
