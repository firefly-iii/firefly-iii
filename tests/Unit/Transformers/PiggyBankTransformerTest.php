<?php
/**
 * PiggyBankTransformerTest.php
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
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\PiggyBankTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class PiggyBankTransformerTest
 */
class PiggyBankTransformerTest extends TestCase
{
    /**
     * Test basic transformer.
     *
     * @covers \FireflyIII\Transformers\PiggyBankTransformer::transform()
     */
    public function testBasic()
    {
        // mock repository:
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getCurrentAmount')->andReturn('12.34')->once();

        // make new account and piggy
        $account     = Account::create(
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
        $piggy       = PiggyBank::create(
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
        $transformer = new PiggyBankTransformer(new ParameterBag);
        $result      = $transformer->transform($piggy);
        $this->assertTrue($result['active']);
        $this->assertEquals(12.34, $result['current_amount']);
        $this->assertEquals($piggy->name, $result['name']);
        $this->assertEquals('', $result['notes']);
    }

    /**
     * Test basic transformer with currency preference
     *
     * @covers \FireflyIII\Transformers\PiggyBankTransformer::transform()
     */
    public function testBasicWithCurrency()
    {
        // mock repository.
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $currencyRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->once();

        // mock repository:
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getCurrentAmount')->andReturn('12.34')->once();

        // make new account and piggy
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
                'name'       => 'currency_id',
                'data'       => 1,
            ]
        );

        $piggy       = PiggyBank::create(
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
        $transformer = new PiggyBankTransformer(new ParameterBag);
        $result      = $transformer->transform($piggy);
        $this->assertTrue($result['active']);
        $this->assertEquals(12.34, $result['current_amount']);
        $this->assertEquals($piggy->name, $result['name']);
        $this->assertEquals('', $result['notes']);
    }

    /**
     * Test basic transformer with currency preference and a note
     *
     * @covers \FireflyIII\Transformers\PiggyBankTransformer::transform()
     */
    public function testBasicWithCurrencyAndNote()
    {
        // mock repository.
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $currencyRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->once();

        // mock repository:
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getCurrentAmount')->andReturn('12.34')->once();

        // make new account and piggy
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
                'name'       => 'currency_id',
                'data'       => 1,
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

        // note:
        Note::create(
            [
                'noteable_id'   => $piggy->id,
                'noteable_type' => PiggyBank::class,
                'title'         => null,
                'text'          => 'I am a note.',
            ]
        );
        $transformer = new PiggyBankTransformer(new ParameterBag);
        $result      = $transformer->transform($piggy);
        $this->assertTrue($result['active']);
        $this->assertEquals(12.34, $result['current_amount']);
        $this->assertEquals($piggy->name, $result['name']);
        $this->assertEquals('I am a note.', $result['notes']);
    }
}