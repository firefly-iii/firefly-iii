<?php
/**
 * SearchControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Support\Search\SearchInterface;
use Tests\TestCase;

/**
 * Class SearchControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class SearchControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\SearchController::index
     * @covers \FireflyIII\Http\Controllers\SearchController::__construct
     */
    public function testIndex()
    {
        $search = $this->mock(SearchInterface::class);
        $search->shouldReceive('parseQuery')->once();
        $search->shouldReceive('getWordsAsString')->once()->andReturn('test');
        $this->be($this->user());
        $response = $this->get(route('search.index') . '?q=test');
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

}
