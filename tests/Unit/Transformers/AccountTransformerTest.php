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
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use Log;
use Mockery;
use Steam;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class AccountTransformerTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccountTransformerTest extends TestCase
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
     * Check balance on a different date.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testBalanceDate(): void
    {
        // mock stuff and get object:
        $account      = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $parameters = new ParameterBag;
        $parameters->set('date', new Carbon('2018-01-01'));

        $transformer = app(AccountTransformer::class);
        $transformer->setParameters($parameters);

        // following calls are expected:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountType')->andReturn('Asset account')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $accountRepos->shouldReceive('getNoteText')->andReturn('I am a note')->atLeast()->once();

        // get all kinds of meta values:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('defaultAsset')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('12345')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('NL5X')->atLeast()->once();

        // opening balance:
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->withArgs([Mockery::any()])->andReturnNull()->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->withArgs([Mockery::any()])->andReturnNull()->atLeast()->once();


        // steam is also called for the account balance:
        Steam::shouldReceive('balance')->andReturn('123.45')->atLeast()->once();


        $result = $transformer->transform($account);

        // verify all fields.
        $this->assertEquals($account->id, $result['id']);
        $this->assertEquals($account->active, $result['active']);
        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('asset', $result['type']);
        $this->assertEquals('defaultAsset', $result['account_role']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals('€', $result['currency_symbol']);
        $this->assertEquals(2, $result['currency_decimal_places']);

        // date given, so it must match.
        $this->assertEquals('2018-01-01', $result['current_balance_date']);
        $this->assertEquals(123.45, $result['current_balance']);

        $this->assertEquals('I am a note', $result['notes']);
        $this->assertNull($result['monthly_payment_date']);
        $this->assertNull($result['credit_card_type']);
        $this->assertEquals('12345', $result['account_number']);
        $this->assertEquals($account->iban, $result['iban']);
        $this->assertEquals('NL5X', $result['bic']);
        $this->assertNull($result['liability_type']);
        $this->assertNull($result['liability_amount']);
        $this->assertNull($result['liability_start_date']);
        $this->assertNull($result['interest']);
        $this->assertNull($result['interest_period']);
        $this->assertTrue($result['include_net_worth']);

    }

    /**
     * Load a basic asset account, and verify the result in the transformer.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testBasicAsset(): void
    {
        // mock stuff and get object:
        $account      = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $transformer = app(AccountTransformer::class);
        $transformer->setParameters(new ParameterBag);

        // following calls are expected:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountType')->andReturn('Asset account')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $accountRepos->shouldReceive('getNoteText')->andReturn('I am a note')->atLeast()->once();

        // get all kinds of meta values:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('defaultAsset')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('12345')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('NL5X')->atLeast()->once();

        // opening balance:
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->withArgs([Mockery::any()])->andReturnNull()->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->withArgs([Mockery::any()])->andReturnNull()->atLeast()->once();


        // steam is also called for the account balance:
        Steam::shouldReceive('balance')->andReturn('123.45')->atLeast()->once();


        $result = $transformer->transform($account);

        // verify all fields.
        $this->assertEquals($account->id, $result['id']);
        $this->assertEquals($account->active, $result['active']);
        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('asset', $result['type']);
        $this->assertEquals('defaultAsset', $result['account_role']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals('€', $result['currency_symbol']);
        $this->assertEquals(2, $result['currency_decimal_places']);

        // no date given, so must be today:
        $this->assertEquals(date('Y-m-d'), $result['current_balance_date']);
        $this->assertEquals(123.45, $result['current_balance']);

        $this->assertEquals('I am a note', $result['notes']);
        $this->assertNull($result['monthly_payment_date']);
        $this->assertNull($result['credit_card_type']);
        $this->assertEquals('12345', $result['account_number']);
        $this->assertEquals($account->iban, $result['iban']);
        $this->assertEquals('NL5X', $result['bic']);
        $this->assertNull($result['liability_type']);
        $this->assertNull($result['liability_amount']);
        $this->assertNull($result['liability_start_date']);
        $this->assertNull($result['interest']);
        $this->assertNull($result['interest_period']);
        $this->assertTrue($result['include_net_worth']);

    }

    /**
     * Credit card asset has some extra fields
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testCreditCardAsset(): void
    {
        // mock stuff and get object:
        $account      = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $transformer = app(AccountTransformer::class);
        $transformer->setParameters(new ParameterBag);

        // following calls are expected:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountType')->andReturn('Asset account')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $accountRepos->shouldReceive('getNoteText')->andReturn('I am a note')->atLeast()->once();

        // get all kinds of meta values:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('ccAsset')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('12345')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('NL5X')->atLeast()->once();

        // credit card fields:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_type'])->andReturn('monthlyFull')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'cc_monthly_payment_date'])->andReturn('2018-01-01')->atLeast()->once();


        // opening balance:
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->withArgs([Mockery::any()])->andReturnNull()->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->withArgs([Mockery::any()])->andReturnNull()->atLeast()->once();


        // steam is also called for the account balance:
        Steam::shouldReceive('balance')->andReturn('123.45')->atLeast()->once();


        $result = $transformer->transform($account);

        // verify all fields.
        $this->assertEquals($account->id, $result['id']);
        $this->assertEquals($account->active, $result['active']);
        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('asset', $result['type']);
        $this->assertEquals('ccAsset', $result['account_role']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals('€', $result['currency_symbol']);
        $this->assertEquals(2, $result['currency_decimal_places']);

        // no date given, so must be today:
        $this->assertEquals(date('Y-m-d'), $result['current_balance_date']);
        $this->assertEquals(123.45, $result['current_balance']);

        $this->assertEquals('I am a note', $result['notes']);

        // cc fields must be filled in:
        $this->assertEquals('2018-01-01', $result['monthly_payment_date']);
        $this->assertEquals('monthlyFull', $result['credit_card_type']);
        $this->assertEquals('12345', $result['account_number']);
        $this->assertEquals($account->iban, $result['iban']);
        $this->assertEquals('NL5X', $result['bic']);
        $this->assertNull($result['liability_type']);
        $this->assertNull($result['liability_amount']);
        $this->assertNull($result['liability_start_date']);
        $this->assertNull($result['interest']);
        $this->assertNull($result['interest_period']);
        $this->assertTrue($result['include_net_worth']);

    }

    /**
     * Liability also has some extra fields.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testLiability(): void
    {
        // mock stuff and get object:
        $account      = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $transformer = app(AccountTransformer::class);
        $transformer->setParameters(new ParameterBag);

        // following calls are expected:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountType')->andReturn('Mortgage')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $accountRepos->shouldReceive('getNoteText')->andReturn('I am a note')->atLeast()->once();

        // get all kinds of meta values:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('12345')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('NL5X')->atLeast()->once();

        // data for liability
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('3')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly')->atLeast()->once();

        // opening balance:
        $accountRepos->shouldReceive('getOpeningBalanceAmount')->withArgs([Mockery::any()])->andReturn('-1000')->atLeast()->once();
        $accountRepos->shouldReceive('getOpeningBalanceDate')->withArgs([Mockery::any()])->andReturn('2018-01-01')->atLeast()->once();


        // steam is also called for the account balance:
        Steam::shouldReceive('balance')->andReturn('123.45')->atLeast()->once();


        $result = $transformer->transform($account);

        // verify all fields.
        $this->assertEquals($account->id, $result['id']);
        $this->assertEquals($account->active, $result['active']);
        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('liabilities', $result['type']);
        $this->assertNull($result['account_role']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals('€', $result['currency_symbol']);
        $this->assertEquals(2, $result['currency_decimal_places']);

        // no date given, so must be today:
        $this->assertEquals(date('Y-m-d'), $result['current_balance_date']);
        $this->assertEquals(123.45, $result['current_balance']);
        $this->assertEquals('I am a note', $result['notes']);
        $this->assertNull($result['monthly_payment_date']);
        $this->assertNull($result['credit_card_type']);
        $this->assertEquals('12345', $result['account_number']);
        $this->assertEquals($account->iban, $result['iban']);
        $this->assertEquals('NL5X', $result['bic']);

        // liability fields
        $this->assertEquals('mortgage', $result['liability_type']);
        $this->assertEquals('-1000', $result['liability_amount']);
        $this->assertEquals('2018-01-01', $result['liability_start_date']);
        $this->assertEquals('3', $result['interest']);
        $this->assertEquals('monthly', $result['interest_period']);

        $this->assertTrue($result['include_net_worth']);

    }

    /**
     * If the account is not an asset account, the role must always be NULL.
     *
     * @covers \FireflyIII\Transformers\AccountTransformer
     */
    public function testRoleEmpty(): void
    {
        // mock stuff and get object:
        $account      = $this->getRandomExpense();
        $euro         = $this->getEuro();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $transformer = app(AccountTransformer::class);
        $transformer->setParameters(new ParameterBag);

        // following calls are expected:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountType')->andReturn('Expense account')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $accountRepos->shouldReceive('getNoteText')->andReturn('I am a note')->atLeast()->once();

        // get all kinds of meta values:
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_role'])->andReturn('defaultAsset')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('12345')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('NL5X')->atLeast()->once();

        // steam is also called for the account balance:
        Steam::shouldReceive('balance')->andReturn('123.45')->atLeast()->once();


        $result = $transformer->transform($account);

        // verify all fields.
        $this->assertEquals($account->id, $result['id']);
        $this->assertEquals($account->active, $result['active']);
        $this->assertEquals($account->name, $result['name']);
        $this->assertEquals('expense', $result['type']);
        $this->assertNull($result['account_role']);
        $this->assertEquals(1, $result['currency_id']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals('€', $result['currency_symbol']);
        $this->assertEquals(2, $result['currency_decimal_places']);

        // no date given, so must be today:
        $this->assertEquals(date('Y-m-d'), $result['current_balance_date']);
        $this->assertEquals(123.45, $result['current_balance']);

        $this->assertEquals('I am a note', $result['notes']);
        $this->assertNull($result['monthly_payment_date']);
        $this->assertNull($result['credit_card_type']);
        $this->assertEquals('12345', $result['account_number']);
        $this->assertEquals($account->iban, $result['iban']);
        $this->assertEquals('NL5X', $result['bic']);
        $this->assertNull($result['liability_type']);
        $this->assertNull($result['liability_amount']);
        $this->assertNull($result['liability_start_date']);
        $this->assertNull($result['interest']);
        $this->assertNull($result['interest_period']);
        $this->assertTrue($result['include_net_worth']);
    }
}
