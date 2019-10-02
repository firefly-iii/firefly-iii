<?php
/**
 * DecryptDatabaseTest.php
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
/**
 * DecryptDatabaseTest.php
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

namespace Tests\Unit\Console\Commands;


use Crypt;
use FireflyConfig;
use FireflyIII\Models\Account;
use FireflyIII\Models\Configuration;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class DecryptDatabaseTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DecryptDatabaseTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\DecryptDatabase
     */
    public function testHandle(): void
    {
        // create encrypted account:
        $name    = 'Encrypted name';
        $iban    = 'HR1723600001101234565';
        $account = Account::create(
            [
                'user_id'         => 1,
                'account_type_id' => 1,
                'name'            => Crypt::encrypt($name),
                'iban'            => Crypt::encrypt($iban),
            ]);


        FireflyConfig::shouldReceive('get')->withArgs([Mockery::any(), false])->atLeast()->once()->andReturn(null);
        FireflyConfig::shouldReceive('set')->withArgs([Mockery::any(), true])->atLeast()->once();

        $this->artisan('firefly-iii:decrypt-all')
             ->expectsOutput('Done!')
             ->assertExitCode(0);

        $this->assertCount(1, Account::where('id', $account->id)->where('name', $name)->get());
        $this->assertCount(1, Account::where('id', $account->id)->where('iban', $iban)->get());
    }

    /**
     * @covers \FireflyIII\Console\Commands\DecryptDatabase
     */
    public function testHandleDecrypted(): void
    {
        // create encrypted account:
        $name          = 'Encrypted name';
        $iban          = 'HR1723600001101234565';
        $encryptedName = Crypt::encrypt($name);
        $encryptedIban = Crypt::encrypt($iban);
        $account       = Account::create(
            [
                'user_id'         => 1,
                'account_type_id' => 1,
                'name'            => $encryptedName,
                'iban'            => $encryptedIban,
            ]);

        // pretend its not yet decrypted.
        $true       = new Configuration;
        $true->data = true;

        FireflyConfig::shouldReceive('get')->withArgs([Mockery::any(), false])->atLeast()->once()->andReturn($true);

        $this->artisan('firefly-iii:decrypt-all')
             ->expectsOutput('Done!')
             ->assertExitCode(0);

        $this->assertCount(1, Account::where('id', $account->id)->where('name', $encryptedName)->get());
        $this->assertCount(1, Account::where('id', $account->id)->where('iban', $encryptedIban)->get());
    }

    /**
     * Try to decrypt data that isn't actually encrypted.
     *
     * @covers \FireflyIII\Console\Commands\DecryptDatabase
     */
    public function testHandleNotEncrypted(): void
    {
        // create encrypted account:
        $name    = 'Encrypted name';
        $iban    = 'HR1723600001101234565';
        $account = Account::create(
            [
                'user_id'         => 1,
                'account_type_id' => 1,
                'name'            => $name,
                'iban'            => $iban,
            ]);

        // pretend its not yet decrypted.
        $true       = new Configuration;
        $true->data = true;

        FireflyConfig::shouldReceive('get')->withArgs([Mockery::any(), false])->atLeast()->once()->andReturn($true);

        $this->artisan('firefly-iii:decrypt-all')
             ->expectsOutput('Done!')
             ->assertExitCode(0);

        $this->assertCount(1, Account::where('id', $account->id)->where('name', $name)->get());
        $this->assertCount(1, Account::where('id', $account->id)->where('iban', $iban)->get());
    }

}
