<?php
/**
 * TagTransformerTest.php
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

namespace Tests\Unit\Transformers;


use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use FireflyIII\Transformers\TagTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class TagTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
            ]
        );
        $location = new Location;
        $location->latitude = 5.5;
        $location->longitude = 6.6;
        $location->zoom_level = 3;
        $location->locatable()->associate($tag);
        $location->save();

        $transformer = app(TagTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($tag);

        $this->assertEquals($tag->tag, $result['tag']);
        $this->assertEquals(5.5, $result['latitude']);
        $this->assertEquals(6.6, $result['longitude']);
    }

}
