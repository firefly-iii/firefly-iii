<?php
/**
 * AccountCurrenciesTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Upgrade;


use FireflyConfig;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class AccountCurrenciesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccountCurrenciesTest extends TestCase
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
     * Perfect run without opening balance.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandle(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        $pref         = new Preference;
        $pref->data   = 'EUR';
        $account      = $this->getRandomAsset();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('1');
        $accountRepos->shouldReceive('getOpeningBalance')->atLeast()->once()->andReturn(null);
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput('All accounts are OK.')
             ->assertExitCode(0);

        // nothing changed, so nothing to verify.
    }

    /**
     * Perfect run without opening balance.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleNotNull(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        $pref         = new Preference;
        $pref->data   = 'EUR';
        $account      = $this->getRandomAsset();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journal      = $this->getRandomWithdrawal();

        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // account reports USD
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('2');
        // journal is EUR.
        $accountRepos->shouldReceive('getOpeningBalance')->atLeast()->once()->andReturn($journal);
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name))
             ->assertExitCode(0);

        // check if currency has been changed for the journal + transactions.
        $this->assertCount(1, TransactionJournal::where('id', $journal->id)->where('transaction_currency_id', 2)->get());
        $this->assertCount(2, Transaction::where('transaction_journal_id', $journal->id)->where('transaction_currency_id', 2)->get());
    }

    /**
     * Perfect run with opening balance.
     *
     * TODO this method crashes some times but not sure why.
     * 2019-07-27 should be fixed.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleOpeningBalance(): void
    {
        $false                            = new Configuration;
        $false->data                      = false;
        $pref                             = new Preference;
        $pref->data                       = 'EUR';
        $accountRepos                     = $this->mock(AccountRepositoryInterface::class);
        $userRepos                        = $this->mock(UserRepositoryInterface::class);
        $journal                          = $this->getRandomWithdrawal();
        $euro                             = $this->getEuro();
        $journal->transaction_currency_id = $euro->id;
        $journal->save();
        $journal->refresh();

        $account      = $this->getRandomAsset();
        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('1');
        $accountRepos->shouldReceive('getOpeningBalance')->atLeast()->once()->andReturn($journal);
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput('All accounts are OK.')
             ->assertExitCode(0);

        // nothing changed, dont check output.
    }

    /**
     * Perfect run with opening balance with different currencies
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleDifferent(): void
    {
        $false                            = new Configuration;
        $false->data                      = false;
        $pref                             = new Preference;
        $pref->data                       = 'USD';
        $accountRepos                     = $this->mock(AccountRepositoryInterface::class);
        $userRepos                        = $this->mock(UserRepositoryInterface::class);
        $journal                          = $this->getRandomWithdrawal();
        $account                          = $this->getRandomAsset();
        $euro                             = $this->getEuro();
        $journal->transaction_currency_id = $euro->id;
        $journal->save();

        // delete meta data of account just in case:
        AccountMeta::where('account_id', $account->id)->where('name', 'currency_id')->forceDelete();
        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('0');
        $accountRepos->shouldReceive('getOpeningBalance')->atLeast()->once()->andReturn($journal);
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput(sprintf('Account #%d ("%s") now has a currency setting (#%d).', $account->id, $account->name, $euro->id))
             ->assertExitCode(0);

        // verify account meta data change.
        $this->assertCount(1,
                           AccountMeta::where('account_id', $account->id)
                                      ->where('name', 'currency_id')
                                      ->where('data', $euro->id)->get());
    }

    /**
     * No known currency preferences.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleZeroPreference(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        $pref         = new Preference;
        $pref->data   = 'EUR';
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $account      = $this->getRandomAsset();
        $euro         = $this->getEuro();
        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('0');
        $accountRepos->shouldReceive('getOpeningBalance')->atLeast()->once()->andReturn(null);
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput(sprintf('Account #%d ("%s") now has a currency setting (%s).',
                                     $account->id, $account->name, $euro->code
                             ))
             ->expectsOutput('Corrected 1 account(s).')
             ->assertExitCode(0);

        $this->assertCount(1, AccountMeta::where('account_id', $account->id)
                                         ->where('name', 'currency_id')
                                         ->where('data', $euro->id)->get());
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleNoPreference(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        $pref         = new Preference;
        $pref->data   = false;
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $account      = $this->getRandomAsset();
        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn('1');
        $accountRepos->shouldReceive('getOpeningBalance')->atLeast()->once()->andReturn(null);
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput('All accounts are OK.')
             ->assertExitCode(0);
        // nothing changed, so nothing to verify.
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleInvalidPreference(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        $pref         = new Preference;
        $pref->data   = 'ABC';
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $account      = $this->getRandomAsset();

        // mock calls
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$account]));
        $userRepos->shouldReceive('all')->atLeast()->once()->andReturn(new Collection([$this->user()]));

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput('User has a preference for "ABC", but this currency does not exist.')
             ->assertExitCode(0);

        // nothing changed, so nothing to verify.
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\AccountCurrencies
     */
    public function testHandleAlreadyExecuted(): void
    {
        $true       = new Configuration;
        $true->data = true;
        $pref       = new Preference;
        $pref->data = 'EUR';
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);

        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_account_currencies', false])->andReturn($true);
        FireflyConfig::shouldReceive('set')->withArgs(['480_account_currencies', true]);

        // check preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($pref);

        $this->artisan('firefly-iii:account-currencies')
             ->expectsOutput('This command has already been executed.')
             ->assertExitCode(0);
        // nothing changed, so nothing to verify.
    }
}
