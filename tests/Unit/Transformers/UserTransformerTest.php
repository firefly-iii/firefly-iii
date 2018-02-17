<?php
/**
 * UserTransformerTest.php
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


use FireflyIII\Transformers\UserTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class UserTransformerTest
 */
class UserTransformerTest extends TestCase
{

    /**
     * Test basic transformer.
     *
     * @covers \FireflyIII\Transformers\UserTransformer::transform
     */
    public function testBasic()
    {
        $user        = $this->user();
        $transformer = new UserTransformer(new ParameterBag());
        $result      = $transformer->transform($user);

        $this->assertEquals($user->email, $result['email']);
        $this->assertEquals('owner', $result['role']);
    }

    /**
     * Test basic transformer.
     *
     * @covers \FireflyIII\Transformers\UserTransformer::transform
     */
    public function testEmptyUser()
    {
        $user        = $this->emptyUser();
        $transformer = new UserTransformer(new ParameterBag());
        $result      = $transformer->transform($user);

        $this->assertEquals($user->email, $result['email']);
        $this->assertNull($result['role']);
    }

}