<?php
declare(strict_types=1);
/**
 * TransferCurrenciesCorrectionsTest.php
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
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class TransferCurrenciesCorrectionsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransferCurrenciesCorrectionsTest extends TestCase
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
     * Basic test. Assume nothing is wrong.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandle(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        // assume all is well.
        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('All transfers have correct currency information.')
             ->assertExitCode(0);
    }

    /**
     * Basic test. Assume the transfer is OK.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleCorrectTransfer(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        // assume all is well.
        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('All transfers have correct currency information.')
             ->assertExitCode(0);
    }

    /**
     * Journal has bad currency info.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleInvalidJournalCurrency(): void
    {

        $accountRepos                      = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos                     = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos                      = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer                          = $this->getRandomTransfer();
        $euro                              = $this->getEuro();
        $dollar                            = $this->getDollar();
        $transfer->transaction_currency_id = $dollar->id;
        $transfer->save();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 1 transfer(s).')
             ->assertExitCode(0);

        $this->assertCount(1, TransactionJournal::where('id', $transfer->id)->where('transaction_currency_id', 1)->get());
    }

    /**
     * Missing source foreign amount information.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleMissingSourceForeignAmount(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();
        $dollar        = $this->getDollar();

        /** @var Transaction $destination */
        $destination                 = $transfer->transactions()->where('amount', '>', 0)->first();
        $destination->foreign_amount = '100';
        $destination->save();

        /** @var Transaction $destination */
        $source = $transfer->transactions()->where('amount', '<', 0)->first();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1', $dollar->id);

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([$dollar->id])->andReturn($dollar);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 2 transfer(s).')
             ->assertExitCode(0);

        $this->assertCount(1, Transaction::where('id', $source->id)
                                         ->where('foreign_amount', '-100')->get()
        );
    }


    /**
     * Missing source foreign amount information.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleMissingDestForeignAmount(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();
        $dollar        = $this->getDollar();

        /** @var Transaction $destination */
        $destination                 = $transfer->transactions()->where('amount', '>', 0)->first();
        $destination->foreign_amount = null;
        $destination->save();

        /** @var Transaction $destination */
        $source                 = $transfer->transactions()->where('amount', '<', 0)->first();
        $source->foreign_amount = '-100';
        $source->save();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1', $dollar->id);

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([$dollar->id])->andReturn($dollar);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 3 transfer(s).')
             ->assertExitCode(0);

        $this->assertCount(1, Transaction::where('id', $destination->id)
                                         ->where('foreign_amount', '100')->get()
        );
    }


    /**
     * Basic test. The foreign currency is broken and should be corrected.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleMismatchedForeignCurrency(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();
        $dollar        = $this->getDollar();

        // make sure that source and destination have the right currencies beforehand
        $source                          = $transfer->transactions()->where('amount', '<', 0)->first();
        $source->transaction_currency_id = $euro->id;
        $source->save();

        $dest                          = $transfer->transactions()->where('amount', '>', 0)->first();
        $dest->transaction_currency_id = $dollar->id;
        $dest->save();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1', $dollar->id);

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([$dollar->id])->andReturn($dollar);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 1 transfer(s).')
             ->assertExitCode(0);

        // source and destination transaction should be corrected:
        $this->assertCount(1, Transaction::where('id', $source->id)
                                         ->where('transaction_currency_id', $euro->id)
                                         ->where('foreign_currency_id', $dollar->id)
                                         ->get());
    }


    /**
     * Basic test. Source transaction has no currency.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleTransferNoSourceCurrency(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);

        // get source transaction and remove currency:
        /** @var Transaction $source */
        $source                          = $transfer->transactions()->where('amount', '<', 0)->first();
        $source->transaction_currency_id = null;
        $source->save();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 1 transfer(s).')
             ->assertExitCode(0);

        // assume problem is fixed:
        $this->assertCount(1, Transaction::where('id', $source->id)->where('transaction_currency_id', 1)->get());
    }

    /**
     * Basic test. Destination transaction has no currency.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleTransferNoDestCurrency(): void
    {

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();

        // get source transaction and remove currency:
        /** @var Transaction $destination */
        $destination                          = $transfer->transactions()->where('amount', '>', 0)->first();
        $destination->transaction_currency_id = null;
        $destination->save();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 1 transfer(s).')
             ->assertExitCode(0);

        // assume problem is fixed:
        $this->assertCount(1, Transaction::where('id', $destination->id)->where('transaction_currency_id', 1)->get());
    }

    /**
     * Basic test. Source transaction has bad currency.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleTransferBadSourceCurrency(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();

        // get source transaction and remove currency:
        /** @var Transaction $source */
        $source                          = $transfer->transactions()->where('amount', '<', 0)->first();
        $source->transaction_currency_id = 2;
        $source->foreign_amount          = null;
        $source->save();

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // currency repos
        $currencyRepos->shouldReceive('findNull')
                      ->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 1 transfer(s).')
             ->assertExitCode(0);

        // assume problem is fixed:
        $this->assertCount(1, Transaction::where('id', $source->id)->where('transaction_currency_id', 1)->get());
    }

    /**
     * Basic test. Source transaction has bad currency, and this must be fixed.
     *
     * TODO something in this test is too random, and it fails. Not sure why.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransferCurrenciesCorrections
     */
    public function testHandleTransferBadDestCurrency(): void
    {
        Log::warning(sprintf('Now in test %s.', __METHOD__));
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $transfer      = $this->getRandomTransfer();
        $euro          = $this->getEuro();
        $dollar        = $this->getDollar();
        // get destination transaction and remove currency:
        $transfer->transaction_currency_id = $euro->id;
        $transfer->save();

        Log::debug(sprintf('Gave transfer #%d currency EUR', $transfer->id));


        /** @var Transaction $destination */
        $destination                          = $transfer->transactions()->where('amount', '>', 0)->first();
        $destination->transaction_currency_id = $dollar->id;
        $destination->save();

        Log::debug(sprintf('Gave transaction #%d currency USD', $destination->id));

        // mock calls:
        $cliRepos->shouldReceive('getAllJournals')
                     ->withArgs([[TransactionType::TRANSFER]])
                     ->atLeast()->once()->andReturn(new Collection([$transfer]));

        // account repos
        $accountRepos->shouldReceive('getMetaValue')
                     ->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn((string)$euro->id);

        // currency repos
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([$euro->id])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_transfer_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_transfer_currencies', true]);

        $this->artisan('firefly-iii:transfer-currencies')
             ->expectsOutput('Verified currency information of 1 transfer(s).')
             ->assertExitCode(0);

        // assume problem is fixed:
        $this->assertCount(1, Transaction::where('id', $destination->id)->where('transaction_currency_id', $euro->id)->get());
    }

}
