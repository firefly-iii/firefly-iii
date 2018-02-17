<?php
/**
 * PiggyBankEventTransformerTest.php
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

namespace Tests\Unit\Transformers;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Transformers\PiggyBankEventTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class PiggyBankEventTransformerTest
 */
class PiggyBankEventTransformerTest extends TestCase
{
    /**
     * Basic test with no meta data.
     *
     * @covers \FireflyIII\Transformers\PiggyBankEventTransformer::transform
     */
    public function testBasic()
    {
        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );
        $piggy   = PiggyBank::create(
            [
                'account_id'   => $account->id,
                'name'         => 'Some random piggy #' . rand(1, 10000),
                'targetamount' => '1000',
                'startdate'    => '2018-01-01',
                'targetdate'   => '2018-01-31',
                'order'        => 1,
                'active'       => 1,
            ]
        );
        $event   = PiggyBankEvent::create(
            [
                'piggy_bank_id' => $piggy->id,
                'date'          => '2018-01-01',
                'amount'        => '123.45',
            ]
        );

        $transformer = new PiggyBankEventTransformer(new ParameterBag);
        $result      = $transformer->transform($event);
        $this->assertEquals($event->id, $result['id']);
        $this->assertEquals(123.45, $result['amount']);
    }

    /**
     * Basic test with currency meta data.
     *
     * @covers \FireflyIII\Transformers\PiggyBankEventTransformer::transform
     */
    public function testBasicCurrency()
    {
        // mock repository.
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->once();

        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . rand(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // meta
        $accountMeta = AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'         => 'currency_id',
                'data'         => 1,
            ]
        );

        $piggy = PiggyBank::create(
            [
                'account_id'   => $account->id,
                'name'         => 'Some random piggy #' . rand(1, 10000),
                'targetamount' => '1000',
                'startdate'    => '2018-01-01',
                'targetdate'   => '2018-01-31',
                'order'        => 1,
                'active'       => 1,
            ]
        );
        $event = PiggyBankEvent::create(
            [
                'piggy_bank_id' => $piggy->id,
                'date'          => '2018-01-01',
                'amount'        => '123.45',
            ]
        );

        $transformer = new PiggyBankEventTransformer(new ParameterBag);
        $result      = $transformer->transform($event);
        $this->assertEquals($event->id, $result['id']);
        $this->assertEquals(123.45, $result['amount']);
    }

}