<?php
/**
 * AppendHashTest.php
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

namespace Tests\Unit\Import\Specifics;


use FireflyIII\Import\Specifics\AppendHash;
use Tests\TestCase;
use Log;

/**
 * Class AppendHashTest
 */
class AppendHashTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * Test consistency between runs.
     *
     * @covers \FireflyIII\Import\Specifics\AppendHash
     */
    public function testSeparateRuns(): void
    {
        $row = [0, 'XX', 2, ''];

        $parser = new AppendHash;
        $result = $parser->run($row);
        $this->assertEquals("d930258a40708a1d100108ad49add06bb547c0406ebc2490db7bf9c1855b899a", $result[4]);
        
        $parser = new AppendHash;
        $result = $parser->run($row);
        $this->assertEquals("d930258a40708a1d100108ad49add06bb547c0406ebc2490db7bf9c1855b899a", $result[4]);
    }


    /**
     * Test counting in a run.
     *
     * @covers \FireflyIII\Import\Specifics\AppendHash
     */
    public function testSingleRun(): void
    {
        $row = [0, 'XX', 2, ''];

        $parser = new AppendHash;
        $result = $parser->run($row);
        $this->assertEquals("d930258a40708a1d100108ad49add06bb547c0406ebc2490db7bf9c1855b899a", $result[4]);
        
        $result = $parser->run($row);
        $this->assertEquals("465b8b44511a5ce7d86fef7c76e7783209dd84b6d18252f63a91d830a46d82c2", $result[4]);
    }
}
