<?php
/**
 * AccountTransformerTest.php
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

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use Mockery;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class AccountTransformerTest
 */
class AccountTransformerTest extends TestCase
{
    /**
     * Basic account display.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testBasic(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturn(null);
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturn(null);
        $accountRepos->shouldReceive('getMetaValue')->andReturn('1');
        $accountRepos->shouldReceive('getNoteText')->andReturn('');

        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        $transformer = new AccountTransformer(new ParameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Asset account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(12.34, $result['current_balance']);
        $this->assertNull($result['opening_balance']);
        $this->assertNull($result['opening_balance_date']);
    }

    /**
     * Basic account display with custom date parameter.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testBasicDate(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturn(null);
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturn(null);
        $accountRepos->shouldReceive('getMetaValue')->andReturn('1');
        $accountRepos->shouldReceive('getNoteText')->andReturn('');
        // make new account:
        $account      = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );
        $parameterBag = new ParameterBag;
        $parameterBag->set('date', new Carbon('2018-01-01'));

        $transformer = new AccountTransformer($parameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Asset account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(12.34, $result['current_balance']);
        $this->assertEquals('2018-01-01', $result['current_balance_date']);
    }

    /**
     * Assert account has credit card meta data, should NOT be ignored in output.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testCCDataAsset(): void
    {
        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // add a note:
        $note = Note::create(
            [
                'noteable_id'   => $account->id,
                'noteable_type' => Account::class,
                'title'         => null,
                'text'          => 'I am a note #' . random_int(1, 10000),
            ]
        );

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccMonthlyPaymentDate'])->andReturn('2018-02-01');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'ccType'])->andReturn('monthlyFull');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('123');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('123');
        $accountRepos->shouldReceive('getNoteText')->andReturn($note->text);
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturn(null);
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturn(null);

        // add currency preference:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'currency_id',
                'data'       => 1, // euro
            ]
        );


        // add credit card meta data (will be ignored)
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'accountRole',
                'data'       => 'ccAsset',
            ]
        );
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'ccMonthlyPaymentDate',
                'data'       => '2018-02-01',
            ]
        );
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'ccType',
                'data'       => 'monthlyFull',
            ]
        );


        $transformer = new AccountTransformer(new ParameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Asset account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(12.34, $result['current_balance']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals($note->text, $result['notes']);
        $this->assertEquals('2018-02-01', $result['monthly_payment_date']);
        $this->assertEquals('monthlyFull', $result['credit_card_type']);
        $this->assertEquals('ccAsset', $result['role']);
    }

    /**
     * Expense account has credit card meta data, should be ignored in output.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testIgnoreCCExpense(): void
    {

        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 4, // expense account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );
        // add a note:
        $note = Note::create(
            [
                'noteable_id'   => $account->id,
                'noteable_type' => Account::class,
                'title'         => null,
                'text'          => 'I am a note #' . random_int(1, 10000),
            ]
        );

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getMetaValue')->andReturn('1');
        $accountRepos->shouldReceive('getNoteText')->andReturn($note->text);


        // add currency preference:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'currency_id',
                'data'       => 1, // euro
            ]
        );


        // add credit card meta data (will be ignored)
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'accountRole',
                'data'       => 'ccAsset',
            ]
        );
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'ccMonthlyPaymentDate',
                'data'       => '2018-02-01',
            ]
        );
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'ccType',
                'data'       => 'monthlyFull',
            ]
        );


        $transformer = new AccountTransformer(new ParameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Expense account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(12.34, $result['current_balance']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals($note->text, $result['notes']);
        $this->assertNull($result['monthly_payment_date']);
        $this->assertNull($result['credit_card_type']);
        $this->assertNull($result['role']);
    }

    /**
     * Basic account display.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testOpeningBalance(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturn('45.67');
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $accountRepos->shouldReceive('getMetaValue')->andReturn('1');
        $accountRepos->shouldReceive('getNoteText')->andReturn('');
        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );

        // create opening balance:
        $journal     = TransactionJournal::create(
            [
                'user_id'                 => $this->user()->id,
                'transaction_type_id'     => 4, // opening balance
                'transaction_currency_id' => 1, // EUR
                'description'             => 'Opening',
                'date'                    => '2018-01-01',
                'completed'               => 1,
                'tag_count'               => 0,
            ]
        );
        $transaction = Transaction::create(
            [
                'account_id'              => $account->id,
                'transaction_journal_id'  => $journal->id,
                'transaction_currency_id' => 1,
                'amount'                  => '45.67',
            ]
        );

        $transformer = new AccountTransformer(new ParameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Asset account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(58.01, $result['current_balance']); // add opening balance.
        $this->assertEquals(45.67, $result['opening_balance']);
        $this->assertEquals('2018-01-01', $result['opening_balance_date']);
    }

    /**
     * Account has currency preference, should be reflected in output.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testWithCurrency(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturn(null);
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturn(null);
        $accountRepos->shouldReceive('getMetaValue')->andReturn('1');
        $accountRepos->shouldReceive('getNote')->andReturn('');
        $accountRepos->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('');
        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );
        // add currency preference:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'currency_id',
                'data'       => 1, // euro
            ]
        );

        $transformer = new AccountTransformer(new ParameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Asset account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(12.34, $result['current_balance']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
    }

    /**
     * Account has notes, should be reflected in output.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testWithNotes(): void
    {
        // make new account:
        $account = Account::create(
            [
                'user_id'         => $this->user()->id,
                'account_type_id' => 3, // asset account
                'name'            => 'Random name #' . random_int(1, 10000),
                'virtual_balance' => 12.34,
                'iban'            => 'NL85ABNA0466812694',
                'active'          => 1,
                'encrypted'       => 0,
            ]
        );
        // add a note:
        $note = Note::create(
            [
                'noteable_id'   => $account->id,
                'noteable_type' => Account::class,
                'title'         => null,
                'text'          => 'I am a note #' . random_int(1, 10000),
            ]
        );

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->andReturn(null);
        $accountRepos->shouldReceive('getOpeningBalanceDate')->andReturn(null);
        $accountRepos->shouldReceive('getMetaValue')->andReturn('1');
        $accountRepos->shouldReceive('getNoteText')->andReturn($note->text);

        // add currency preference:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'currency_id',
                'data'       => 1, // euro
            ]
        );


        $transformer = new AccountTransformer(new ParameterBag);
        $result      = $transformer->transform($account);

        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('Asset account', $result['type']);
        $this->assertEquals(12.34, $result['virtual_balance']);
        $this->assertEquals(12.34, $result['current_balance']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals($note->text, $result['notes']);
    }


}
