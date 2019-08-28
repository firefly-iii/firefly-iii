<?php
declare(strict_types=1);
/**
 * UniqueIbanTest.php
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

namespace Tests\Unit\Rules;


use FireflyIII\Rules\UniqueIban;
use Log;
use Tests\TestCase;

/**
 * Class UniqueIbanTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UniqueIbanTest extends TestCase
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
     * @covers \FireflyIII\Rules\UniqueIban
     */
    public function testBasic(): void
    {
        $asset       = $this->getRandomAsset();
        $iban        = $asset->iban;
        $asset->iban = 'NL123';
        $asset->save();

        $this->be($this->user());

        $engine = new UniqueIban(null, 'asset');
        $this->assertFalse($engine->passes('not-important', $asset->iban));

        $asset->iban = $iban;
        $asset->save();
    }

    /**
     * @covers \FireflyIII\Rules\UniqueIban
     */
    public function testBasicSkipExisting(): void
    {
        $asset       = $this->getRandomAsset();
        $iban        = $asset->iban;
        $asset->iban = 'NL123';
        $asset->save();

        $this->be($this->user());

        $engine = new UniqueIban($asset, 'asset');
        $this->assertTrue($engine->passes('not-important', $asset->iban));

        $asset->iban = $iban;
        $asset->save();
    }

    /**
     * @covers \FireflyIII\Rules\UniqueIban
     */
    public function testRevenue(): void
    {
        // give revenue account new IBAN.
        // should be OK to give it to an expense account
        $revenue       = $this->getRandomRevenue();
        $iban          = $revenue->iban;
        $revenue->iban = 'NL123';
        $revenue->save();

        $this->be($this->user());

        // returns true because this mix is OK.
        $engine = new UniqueIban(null, 'expense');
        $this->assertTrue($engine->passes('not-important', 'NL123'));


        $revenue->iban = $iban;
        $revenue->save();
    }


    /**
     * @covers \FireflyIII\Rules\UniqueIban
     */
    public function testExpense(): void
    {
        // give expense account new IBAN.
        // should be OK to give it to an expense account
        $expense       = $this->getRandomExpense();
        $iban          = $expense->iban;
        $expense->iban = 'NL123';
        $expense->save();

        $this->be($this->user());

        // returns true because this mix is OK.
        $engine = new UniqueIban(null, 'revenue');
        $this->assertTrue($engine->passes('not-important', 'NL123'));

        $expense->iban = $iban;
        $expense->save();
    }

    /**
     * @covers \FireflyIII\Rules\UniqueIban
     */
    public function testRevenueAsset(): void
    {
        // give revenue account new IBAN.
        // should be OK to give it to an expense account
        $revenue       = $this->getRandomRevenue();
        $iban          = $revenue->iban;
        $revenue->iban = 'NL123';
        $revenue->save();

        $this->be($this->user());

        // returns false because this mix is not OK.
        $engine = new UniqueIban(null, 'asset');
        $this->assertFalse($engine->passes('not-important', 'NL123'));


        $revenue->iban = $iban;
        $revenue->save();
    }

}
