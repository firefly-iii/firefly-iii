<?php
/**
 * TagTransformerTest.php
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

namespace Tests\Unit\Transformers;


use FireflyIII\Models\Tag;
use FireflyIII\Transformers\TagTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class TagTransformerTest
 */
class TagTransformerTest extends TestCase
{
    /**
     * Test basic tag transformer
     *
     * @covers \FireflyIII\Transformers\TagTransformer
     */
    public function testBasic(): void
    {
        $tag         = Tag::create(
            [
                'user_id'     => $this->user()->id,
                'tag'         => 'Some tag ' . $this->randomInt(),
                'tagMode'     => 'nothing',
                'date'        => '2018-01-01',
                'description' => 'Some tag',
                'latitude'    => 5.5,
                'longitude'   => '6.6',
                'zoomLevel'   => 3,
            ]
        );
        $transformer = app(TagTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($tag);

        $this->assertEquals($tag->tag, $result['tag']);
        $this->assertEquals(5.5, $result['latitude']);
        $this->assertEquals(6.6, $result['longitude']);
    }

}
