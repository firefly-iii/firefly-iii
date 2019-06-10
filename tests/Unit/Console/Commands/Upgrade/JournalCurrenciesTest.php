<?php
/**
 * JournalCurrenciesTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Console\Commands\Upgrade;


use FireflyConfig;
use FireflyIII\Models\Account;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class JournalCurrenciesTest
 */
class JournalCurrenciesTest extends TestCase
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
     * Basic run. Would not change anything.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\JournalCurrencies
     */
    public function testHandle(): void
    {
        // mock classes
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $euro          = TransactionCurrency::find(1);

        // update transfer if necessary for the test:
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_journal_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_journal_currencies', true]);

        // mock stuff
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->andReturn(new Collection);

        // for the "other journals" check, return nothing.
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection);

        // transaction would be verified, nothing more.
        $this->artisan('firefly-iii:journal-currencies')
             ->expectsOutput('All transactions are correct.')
             ->assertExitCode(0);
        // nothing changed, so no verification.

    }

    /**
     * Submit a single transfer which has no issues.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\JournalCurrencies
     */
    public function testHandleTransfer(): void
    {
        // mock classes
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $euro          = TransactionCurrency::find(1);
        $transfer      = $this->getRandomTransfer();

        // update transfer if necessary for the test:
        $collection  = new Collection([$transfer]);
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_journal_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_journal_currencies', true]);

        // mock stuff
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return single tranfer
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->andReturn($collection);

        // for the "other journals" check, return nothing.
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection);

        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'currency_id'])->andReturn($euro->id);
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($euro);

        // transaction would be verified, nothing more.
        $this->artisan('firefly-iii:journal-currencies')
             ->expectsOutput('Verified 1 transaction(s) and journal(s).')
             ->assertExitCode(0);
        // nothing changed, so no verification.
    }

    /**
     * Submit a single transfer where the source account has no currency preference.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\JournalCurrencies
     */
    public function testHandleTransferSourceNoPref(): void
    {
        // mock classes
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $euro          = TransactionCurrency::find(1);
        $transfer      = $this->getRandomTransfer();

        // edit source to remove currency preference:
        /** @var Account $source */
        $source = $transfer->transactions()->where('amount', '<', 0)->first()->account;
//        AccountMeta::where('account_id', $source->id)->where('name', 'currency_id')->delete();

        // update transfer if necessary for the test:
        $collection  = new Collection([$transfer]);
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_journal_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_journal_currencies', true]);

        // mock stuff
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return single transfer
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->andReturn($collection);

        // for the "other journals" check, return nothing.
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection);

        // return NULL for first currency ID and currency.
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'currency_id'])->andReturnNull();
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->andReturnNull();

        // transaction would be verified, nothing more.
        $this->artisan('firefly-iii:journal-currencies')
             ->expectsOutput(sprintf('Account #%d ("%s") must have currency preference but has none.', $source->id, $source->name))
             ->assertExitCode(0);
        // nothing changed, so no verification.

    }

    /**
     * Submit a single transfer where the source transaction has no currency set.
     * Because this is not done over repositories, we must edit the DB.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\JournalCurrencies
     */
    public function testHandleTransferSourceNoCurrency(): void
    {
        // mock classes
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $euro          = TransactionCurrency::find(1);
        $transfer      = $this->getRandomTransfer();
        /** @var Transaction $source */
        $source                          = $transfer->transactions()->where('amount', '<', 0)->first();
        $source->transaction_currency_id = null;
        $source->save();

        // update transfer if necessary for the test:
        $collection  = new Collection([$transfer]);
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_journal_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_journal_currencies', true]);

        // mock stuff
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return single tranfer
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->andReturn($collection);

        // for the "other journals" check, return nothing.
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection);

        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'currency_id'])->andReturn($euro->id);
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($euro);

        // transaction would be verified, nothing more.
        $this->artisan('firefly-iii:journal-currencies')
             ->expectsOutput(sprintf('Transaction #%d has no currency setting, now set to %s.', $source->id, $euro->code))
             ->expectsOutput('Verified 2 transaction(s) and journal(s).')
             ->assertExitCode(0);

        // check transaction
        $this->assertCount(1, Transaction::where('id', $source->id)->where('transaction_currency_id', $euro->id)->get());
    }

    /**
     * Submit a single transfer where the source transaction has a different currency than the source account does.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\JournalCurrencies
     */
    public function testHandleMismatchedTransfer(): void
    {
        // mock classes
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $euro          = TransactionCurrency::find(1);
        $usd           = TransactionCurrency::where('code', 'USD')->first();
        $transfer      = $this->getRandomTransfer();

        /** @var Transaction $source */
        $source                          = $transfer->transactions()->where('amount', '<', 0)->first();
        $source->transaction_currency_id = $usd->id;
        $source->save();

        // update transfer if necessary for the test:
        $collection  = new Collection([$transfer]);
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_journal_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_journal_currencies', true]);

        // mock stuff
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return single tranfer
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->andReturn($collection);

        // for the "other journals" check, return nothing.
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection);

        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'currency_id'])->andReturn($euro->id);
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($euro);

        // transaction would be verified, nothing more.
        $this->artisan('firefly-iii:journal-currencies')
             ->expectsOutput(
                 sprintf(
                     'Transaction #%d has a currency setting #%d that should be #%d. Amount remains %s, currency is changed.',
                     $source->id,
                     $source->transaction_currency_id,
                     $euro->id,
                     $source->amount
                 )
             )
             ->expectsOutput('Verified 2 transaction(s) and journal(s).')
             ->assertExitCode(0);
        // nothing changed, so no verification.
    }

    /**
     * Submit a single transfer where the destination account has no currency preference.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\JournalCurrencies
     */
    public function testHandleTransferNoDestinationCurrency(): void
    {
        // mock classes
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $euro          = TransactionCurrency::find(1);
        $transfer      = $this->getRandomTransfer();

        /** @var Account $destination */
        $destination = $transfer->transactions()->where('amount', '>', 0)->first()->account;

        // update transfer if necessary for the test:
        $collection  = new Collection([$transfer]);
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_journal_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_journal_currencies', true]);

        // mock stuff
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();

        // return single tranfer
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->andReturn($collection);

        // for the "other journals" check, return nothing.
        $journalRepos->shouldReceive('getAllJournals')->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection);

        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->withArgs([Mockery::any(), 'currency_id'])->andReturn($euro->id, 0);
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($euro, null);

        // transaction would be verified, nothing more.
        $this->artisan('firefly-iii:journal-currencies')
             ->expectsOutput(sprintf('Account #%d ("%s") must have currency preference but has none.', $destination->id, $destination->name))
             ->assertExitCode(0);
        // nothing changed, so no verification.
    }


}