<?php
declare(strict_types=1);
/**
 * OtherCurrenciesCorrectionsTest.php
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
 * Class OtherCurrenciesCorrectionsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OtherCurrenciesCorrectionsTest extends TestCase
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
     * Basic test. Assume nothing is wrong. Submit a withdrawal and a deposit.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\OtherCurrenciesCorrections
     */
    public function testHandle(): void
    {
        // mock classes:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $withdrawal    = $this->getRandomWithdrawal();
        $deposit       = $this->getRandomDeposit();
        $euro          = $this->getEuro();

        // collect all journals:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $cliRepos->shouldReceive('setUser')->atLeast()->once();
        $cliRepos->shouldReceive('getAllJournals')
                     ->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection([$withdrawal, $deposit]));

        // account repos
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // collect currency
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_other_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_other_currencies', true]);

        // assume all is well.
        $this->artisan('firefly-iii:other-currencies')
             ->expectsOutput('Verified 2 transaction(s) and journal(s).')
             ->assertExitCode(0);
    }

    /**
     * Basic test. Assume nothing is wrong. Submit an opening balance.
     * Also fixes transaction currency ID.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\OtherCurrenciesCorrections
     */
    public function testHandleOB(): void
    {
        $type    = TransactionType::where('type', TransactionType::OPENING_BALANCE)->first();
        $asset   = $this->getRandomAsset();
        $initial = $this->getRandomInitialBalance();
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $type->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $asset->id,
                'amount'                 => '-10',
                'identifier'             => 1,
            ]
        );
        $two     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $initial->id,
                'amount'                 => '10',
                'identifier'             => 1,
            ]
        );


        // mock classes:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $euro          = $this->getEuro();

        // collect all journals:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $cliRepos->shouldReceive('setUser')->atLeast()->once();
        $cliRepos->shouldReceive('getAllJournals')
                     ->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection([$journal]));

        // account repos
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // collect currency
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_other_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_other_currencies', true]);

        $this->artisan('firefly-iii:other-currencies')
             ->expectsOutput('Verified 1 transaction(s) and journal(s).')
             ->assertExitCode(0);

        // assume currency has been fixed for both transactions:
        $this->assertCount(1, Transaction::where('id', $one->id)->where('transaction_currency_id', $euro->id)->get());
        $this->assertCount(1, Transaction::where('id', $two->id)->where('transaction_currency_id', $euro->id)->get());

        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }

    /**
     * Basic test. Assume nothing is wrong. Submit an opening balance.
     * Also fixes bad transaction currency ID.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\OtherCurrenciesCorrections
     */
    public function testHandleReconciliation(): void
    {
        $type           = TransactionType::where('type', TransactionType::RECONCILIATION)->first();
        $asset          = $this->getRandomAsset();
        $reconciliation = $this->getRandomReconciliation();
        $journal        = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $type->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one            = Transaction::create(
            [
                'transaction_journal_id'  => $journal->id,
                'account_id'              => $asset->id,
                'amount'                  => '-10',
                'identifier'              => 1,
                'transaction_currency_id' => 2,
            ]
        );
        $two            = Transaction::create(
            [
                'transaction_journal_id'  => $journal->id,
                'account_id'              => $reconciliation->id,
                'amount'                  => '10',
                'identifier'              => 1,
                'transaction_currency_id' => 2,
            ]
        );


        // mock classes:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $cliRepos      = $this->mock(JournalCLIRepositoryInterface::class);
        $euro          = $this->getEuro();

        // collect all journals:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $cliRepos->shouldReceive('setUser')->atLeast()->once();
        $cliRepos->shouldReceive('getAllJournals')
                     ->atLeast()->once()
                     ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,]])
                     ->andReturn(new Collection([$journal]));

        // account repos
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        // collect currency
        $currencyRepos->shouldReceive('setUser')->atLeast()->once();
        $currencyRepos->shouldReceive('findNull')->atLeast()->once()
                      ->withArgs([1])->andReturn($euro);

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_other_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['4780_other_currencies', true]);

        $this->artisan('firefly-iii:other-currencies')
             ->expectsOutput('Verified 1 transaction(s) and journal(s).')
             ->assertExitCode(0);

        // assume currency has been fixed for both transactions:
        $this->assertCount(1, Transaction::where('id', $one->id)->where('transaction_currency_id', $euro->id)->get());
        $this->assertCount(1, Transaction::where('id', $two->id)->where('transaction_currency_id', $euro->id)->get());
        $this->assertCount(1, Transaction::where('id', $one->id)->where('foreign_currency_id', 2)->get());
        $this->assertCount(1, Transaction::where('id', $two->id)->where('foreign_currency_id', 2)->get());

        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }


}
