<?php
/**
 * TagsSpaceTest.php
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

namespace Tests\Unit\Import\MapperPreProcess;

use FireflyIII\Import\MapperPreProcess\TagsSpace;
use Log;
use Tests\TestCase;

/**
 * Class TagsSpaceTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TagsSpaceTest extends TestCase
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
     * \FireflyIII\Import\MapperPreProcess\TagsSpace
     */
    public function testBasic(): void
    {
        $input  = 'some tags with  spaces,and without  ';
        $output = ['some', 'tags', 'with', 'spaces,and', 'without'];
        $mapper = new TagsSpace();
        $result = $mapper->run($input);

        $this->assertEquals($output, $result);
    }

}
