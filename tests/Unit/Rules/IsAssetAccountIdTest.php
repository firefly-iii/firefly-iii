<?php
/**
 * IsAssetAccountIdTest.php
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


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Rules\IsAssetAccountId;
use Log;
use Tests\TestCase;

/**
 * Class IsAssetAccountIdTest
 */
class IsAssetAccountIdTest extends TestCase
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
     * @covers \FireflyIII\Rules\IsAssetAccountId
     */
    public function testNotAsset(): void
    {
        $attribute = 'not-used';
        $expense   = $this->getRandomExpense();
        $value     = $expense->id;

        $engine = new IsAssetAccountId();
            $this->assertFalse($engine->passes($attribute, $value));
    }


    /**
     * @covers \FireflyIII\Rules\IsAssetAccountId
     */
    public function testAsset(): void
    {
        $attribute = 'not-used';
        $asset   = $this->getRandomAsset();
        $value     = $asset->id;

        $engine = new IsAssetAccountId();
            $this->assertTrue($engine->passes($attribute, $value));
    }

    /**
     * @covers \FireflyIII\Rules\IsAssetAccountId
     */
    public function testNull(): void
    {
        $attribute = 'not-used';
        $value     = '-1';

        $engine = new IsAssetAccountId();
            $this->assertFalse($engine->passes($attribute, $value));
    }

}