<?php
/**
 * UserTransformerTest.php
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


use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\UserTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class UserTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserTransformerTest extends TestCase
{

    /**
     * Test basic transformer.
     *
     * @covers \FireflyIII\Transformers\UserTransformer
     */
    public function testBasic(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('getRoleByUser')->atLeast()->once()->andReturn('owner');
        $user = $this->user();

        $transformer = app(UserTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($user);

        $this->assertEquals($user->email, $result['email']);
        $this->assertEquals('owner', $result['role']);
    }
}
