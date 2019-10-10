<?php
/**
 * AddTagTest.php
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

namespace Tests\Unit\TransactionRules\Actions;

use FireflyIII\Factory\TagFactory;
use FireflyIII\Models\RuleAction;
use FireflyIII\TransactionRules\Actions\AddTag;
use Log;
use Tests\TestCase;

/**
 * Class AddTagTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AddTagTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Actions\AddTag
     */
    public function testActExistingTag(): void
    {
        $tagFactory = $this->mock(TagFactory::class);
        $tag        = $this->getRandomTag();
        $journal    = $this->getRandomWithdrawal();

        // make sure journal has no tags:
        $journal->tags()->sync([]);
        $journal->save();

        // add single existing tag:
        $journal->tags()->sync([$tag->id]);


        $tagFactory->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs([$tag->tag])->andReturn($tag);

        // assert connection exists.
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);

        // file action
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $tag->tag;
        $action = new AddTag($ruleAction);
        $result = $action->act($journal);
        $this->assertFalse($result);

        // assert DB is unchanged.
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Actions\AddTag
     */
    public function testActNewTag(): void
    {
        $tagFactory = $this->mock(TagFactory::class);
        $tag        = $this->getRandomTag();
        $journal    = $this->getRandomWithdrawal();

        // make sure journal has no tags:
        $journal->tags()->sync([]);
        $journal->save();

        $tagFactory->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs([$tag->tag])->andReturn($tag);

        // assert connection does not exist.
        $this->assertDatabaseMissing('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);

        // file action
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $tag->tag;
        $action = new AddTag($ruleAction);
        $result = $action->act($journal);
        $this->assertTrue($result);

        // assert DB is unchanged.
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\AddTag
     */
    public function testActNullTag(): void
    {
        // try to add non-existing tag
        $tagFactory = $this->mock(TagFactory::class);
        $newTagName = 'TestTag-' . $this->randomInt();

        // should return null:
        $tagFactory->shouldReceive('setUser')->once();
        $tagFactory->shouldReceive('findOrCreate')->once()->withArgs([$newTagName])->andReturnNull();

        $journal                  = $this->getRandomWithdrawal();
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newTagName;
        $action                   = new AddTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }
}
