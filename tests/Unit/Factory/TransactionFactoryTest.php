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


use FireflyIII\Factory\TransactionFactory;
use Log;
use Tests\TestCase;

/**
 * Class TransactionFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
    public function testCreateNegative(): void
    {
        // data used in calls.
        $journal = $this->getRandomWithdrawal();
        $account = $this->getRandomAsset();
        $euro    = $this->getEuro();
        $amount  = '10';

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);


        // set details:
        $factory->setUser($this->user());
        $factory->setJournal($journal);
        $factory->setAccount($account);
        $factory->setCurrency($euro);
        $factory->setReconciled(false);

        // create negative
        $transaction = $factory->createNegative($amount, null);

        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals('-10.000000000000', $transaction->amount);
        $transaction->forceDelete();
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreatePositive(): void
    {
        // data used in calls.
        $journal = $this->getRandomWithdrawal();
        $account = $this->getRandomAsset();
        $euro    = $this->getEuro();
        $amount  = '10';

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);


        // set details:
        $factory->setUser($this->user());
        $factory->setJournal($journal);
        $factory->setAccount($account);
        $factory->setCurrency($euro);
        $factory->setReconciled(false);

        // create positive
        $transaction = $factory->createPositive($amount, null);

        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals('10', $transaction->amount);
        $transaction->forceDelete();
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreateNegativeForeign(): void
    {
        // data used in calls.
        $journal = $this->getRandomWithdrawal();
        $account = $this->getRandomAsset();
        $euro    = $this->getEuro();
        $dollar  = $this->getDollar();
        $amount  = '10';

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);


        // set details:
        $factory->setUser($this->user());
        $factory->setJournal($journal);
        $factory->setAccount($account);
        $factory->setCurrency($euro);
        $factory->setForeignCurrency($dollar);
        $factory->setReconciled(false);

        // create negative
        $transaction = $factory->createNegative($amount, $amount);

        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals('-10.000000000000', $transaction->amount);
        $this->assertEquals('-10.000000000000', $transaction->foreign_amount);
        $this->assertEquals($euro->id, $transaction->transaction_currency_id);
        $this->assertEquals($dollar->id, $transaction->foreign_currency_id);
        $transaction->forceDelete();
    }

    /**
     * @covers \FireflyIII\Factory\TransactionFactory
     */
    public function testCreatePositiveForeign(): void
    {
        // data used in calls.
        $journal = $this->getRandomWithdrawal();
        $account = $this->getRandomAsset();
        $euro    = $this->getEuro();
        $dollar  = $this->getDollar();
        $amount  = '10';

        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);


        // set details:
        $factory->setUser($this->user());
        $factory->setJournal($journal);
        $factory->setAccount($account);
        $factory->setCurrency($euro);
        $factory->setForeignCurrency($dollar);
        $factory->setReconciled(false);

        // create positive
        $transaction = $factory->createPositive($amount, $amount);
        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals('10', $transaction->amount);
        $this->assertEquals('10', $transaction->foreign_amount);
        $this->assertEquals($euro->id, $transaction->transaction_currency_id);
        $this->assertEquals($dollar->id, $transaction->foreign_currency_id);

        $transaction->forceDelete();
    }
}
