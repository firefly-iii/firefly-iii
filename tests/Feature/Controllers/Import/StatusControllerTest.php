<?php
/**
 * StatusControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Import;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatusControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('import.status', ['configured']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::index
     */
    public function testIndexRedirect()
    {
        $this->be($this->user());
        $response = $this->get(route('import.status', ['new']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['new']));

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::json
     */
    public function testStatusFinished()
    {
        $tag        = $this->user()->tags()->first();
        $repository = $this->mock(TagRepositoryInterface::class);
        $repository->shouldReceive('find')->andReturn($tag);
        $this->be($this->user());
        $response = $this->get(route('import.status.json', ['finished']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\StatusController::json
     */
    public function testStatusRunning()
    {
        $this->be($this->user());
        $response = $this->get(route('import.status.json', ['running']));
        $response->assertStatus(200);
    }

}
