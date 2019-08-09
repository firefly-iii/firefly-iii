<?php
/**
 * IsBooleanTest.php
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
use FireflyIII\Rules\IsBoolean;
use Log;
use Tests\TestCase;

/**
 * Class IsBooleanTest
 */
class IsBooleanTest extends TestCase
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
     * @covers \FireflyIII\Rules\IsBoolean
     */
    public function testFalse(): void
    {
        $attribute = 'not-important';

        $false = ['not', 2, -1, []];

        /** @var mixed $value */
        foreach ($false as $value) {

            $engine = new IsBoolean();
                $this->assertFalse($engine->passes($attribute, $value));
        }
    }

    /**
     * @covers \FireflyIII\Rules\IsBoolean
     */
    public function testTrue(): void
    {
        $attribute = 'not-important';

        $true = [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'];

        /** @var mixed $value */
        foreach ($true as $value) {

            $engine = new IsBoolean();
                $this->assertTrue($engine->passes($attribute, $value));
        }
    }

}