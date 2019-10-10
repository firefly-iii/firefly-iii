<?php
/**
 * TagIsTest.php
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

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\TagIs;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class TagIsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TagIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs
     */
    public function testNotTriggered(): void
    {
        $journal = $this->getRandomWithdrawal();
        $journal->tags()->detach();
        $this->assertEquals(0, $journal->tags()->count());

        $trigger = TagIs::makeFromStrings('SomeTag', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs
     */
    public function testTriggered(): void
    {
        $journal = $this->getRandomWithdrawal();
        $journal->tags()->detach();
        /** @var Collection $tags */
        $tags   = $journal->user->tags()->take(3)->get();
        $search = '';
        foreach ($tags as $index => $tag) {
            $journal->tags()->save($tag);
            if (1 === $index) {
                $search = $tag->tag;
            }
        }
        $this->assertEquals(3, $journal->tags()->count());

        $trigger = TagIs::makeFromStrings($search, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $value  = '';
        $result = TagIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = TagIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = TagIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
