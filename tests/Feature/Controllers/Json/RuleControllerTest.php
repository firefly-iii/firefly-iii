<?php
/**
 * RuleControllerTest.php
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

namespace Tests\Feature\Controllers;

use Log;
use Tests\TestCase;

/**
 * Class RuleControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Json\RuleController
     */
    public function testAction(): void
    {
        $this->mockDefaultSession();
        // mock stuff

        $this->be($this->user());
        $response = $this->get(route('json.action'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\RuleController
     */
    public function testTrigger(): void
    {
        $this->mockDefaultSession();
        // mock stuff

        $this->be($this->user());
        $response = $this->get(route('json.trigger'));
        $response->assertStatus(200);
    }
}
