<?php
/**
 * JournalMetaTransformerTest.php
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

use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Transformers\JournalMetaTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class JournalMetaTransformerTest
 */
class JournalMetaTransformerTest extends TestCase
{
    /**
     * Basic coverage
     *
     * @covers  \FireflyIII\Transformers\JournalMetaTransformer::transform
     */
    public function testBasic()
    {
        $data = 'Lots of data';
        $hash = hash('sha256', json_encode($data));
        $meta = TransactionJournalMeta::create(
            [
                'transaction_journal_id' => 1,
                'name'                   => 'someField',
                'data'                   => $data,
            ]
        );

        $transformer = new JournalMetaTransformer(new ParameterBag);
        $result      = $transformer->transform($meta);

        $this->assertEquals($meta->name, $result['name']);
        $this->assertEquals($hash, $result['hash']);
    }

}