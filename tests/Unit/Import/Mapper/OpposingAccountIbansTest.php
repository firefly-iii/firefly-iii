<?php
/**
 * OpposingAccountIbansTest.php
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

namespace Tests\Unit\Import\Mapper;

use FireflyIII\Import\Mapper\OpposingAccountIbans;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class OpposingAccountIbansTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OpposingAccountIbansTest extends TestCase
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
     * @covers \FireflyIII\Import\Mapper\OpposingAccountIbans
     */
    public function testGetMapBasic(): void
    {
        $asset                = AccountType::where('type', AccountType::ASSET)->first();
        $loan                 = AccountType::where('type', AccountType::LOAN)->first();
        $one                  = new Account;
        $one->id              = 21;
        $one->name            = 'Something';
        $one->iban            = 'IBAN';
        $one->account_type_id = $asset->id;

        $two                  = new Account;
        $two->id              = 17;
        $two->name            = 'Else';
        $two->account_type_id = $loan->id;

        $three                  = new Account;
        $three->id              = 66;
        $three->name            = 'I have IBAN';
        $three->iban            = 'IBAN';
        $three->account_type_id = $loan->id;

        $collection = new Collection([$one, $two, $three]);

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->withArgs(
            [[AccountType::DEFAULT, AccountType::ASSET, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::REVENUE, AccountType::LOAN,
              AccountType::DEBT, AccountType::CREDITCARD, AccountType::MORTGAGE,]]
        )->andReturn($collection)->once();

        $mapper  = new OpposingAccountIbans();
        $mapping = $mapper->getMap();
        $this->assertCount(4, $mapping);
        // assert this is what the result looks like:
        $result = [
            0  => (string)trans('import.map_do_not_map'),
            17 => 'Else (liability)',
            66 => 'IBAN (I have IBAN) (liability)',
            21 => 'IBAN (Something)',
        ];
        $this->assertEquals($result, $mapping);
    }

}
