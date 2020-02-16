<?php
/**
 * LinkTypeTransformerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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


use FireflyIII\Models\LinkType;
use FireflyIII\Transformers\LinkTypeTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 *
 * Class LinkTypeTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LinkTypeTransformerTest extends TestCase
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
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\LinkTypeTransformer
     */
    public function testBasic(): void
    {

        $linkType    = LinkType::first();
        $parameters  = new ParameterBag;
        $transformer = app(LinkTypeTransformer::class);
        $transformer->setParameters($parameters);

        $result = $transformer->transform($linkType);

        $this->assertEquals($linkType->inward, $result['inward']);

    }
}
