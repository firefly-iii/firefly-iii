<?php
/**
 * AttachmentTransformerTest.php
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

use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Transformers\AttachmentTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class AttachmentTransformerTest
 */
class AttachmentTransformerTest extends TestCase
{
    /**
     * Test basic transformer
     *
     * @covers \FireflyIII\Transformers\AttachmentTransformer::transform
     */
    public function testBasic()
    {
        $md5        = md5('hello' . rand(1, 10000));
        $attachment = Attachment::create(
            [
                'user_id'         => $this->user()->id,
                'attachable_id'   => 1,
                'attachable_type' => Account::class,
                'md5'             => $md5,
                'filename'        => 'hello.txt',
                'mime'            => 'text/plain',
                'size'            => 101,
                'uploaded'        => 1,
            ]
        );

        $transformer = new AttachmentTransformer(new ParameterBag);
        $result      = $transformer->transform($attachment);
        $this->assertEquals($md5, $result['md5']);
        $this->assertEquals('hello.txt', $result['filename']);
    }

}