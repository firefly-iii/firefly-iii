<?php
/**
 * PrerequisitesControllerTest.php
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

use FireflyIII\Import\Prerequisites\FilePrerequisites;
use Illuminate\Support\MessageBag;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrerequisitesControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController::index
     */
    public function testIndex()
    {
        $object = $this->mock(FilePrerequisites::class);
        $object->shouldReceive('setUser');
        $object->shouldReceive('hasPrerequisites')->andReturn(true);
        $object->shouldReceive('getView')->andReturn('error'); // does not matter which view is returned
        $object->shouldReceive('getViewParameters')->andReturn([]);
        $this->be($this->user());

        $response = $this->get(route('import.prerequisites', ['file']));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController::index
     */
    public function testIndexRedirect()
    {
        $object = $this->mock(FilePrerequisites::class);
        $object->shouldReceive('setUser');
        $object->shouldReceive('hasPrerequisites')->andReturn(false);
        $this->be($this->user());

        $response = $this->get(route('import.prerequisites', ['file']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.create-job', ['file']));

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController::post
     */
    public function testPost()
    {
        $messageBag = new MessageBag;
        $messageBag->add('nomessage', 'nothing');
        $object = $this->mock(FilePrerequisites::class);
        $object->shouldReceive('setUser');
        $object->shouldReceive('hasPrerequisites')->andReturn(true);
        $object->shouldReceive('storePrerequisites')->andReturn($messageBag);
        $this->be($this->user());

        $response = $this->post(route('import.prerequisites.post', ['file']), []);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('import.prerequisites', ['file']));

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController::post
     */
    public function testPostDone()
    {
        $messageBag = new MessageBag;
        $messageBag->add('nomessage', 'nothing');
        $object = $this->mock(FilePrerequisites::class);
        $object->shouldReceive('setUser');
        $object->shouldReceive('hasPrerequisites')->andReturn(false);
        $this->be($this->user());

        $response = $this->post(route('import.prerequisites.post', ['file']), []);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.create-job', ['file']));

    }
}
