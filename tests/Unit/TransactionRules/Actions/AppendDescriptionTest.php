<?php
/**
 * AppendDescriptionTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Actions;


use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\AppendDescription;
use Tests\TestCase;

class AppendDescriptionTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\AppendDescription::__construct
     * @covers \FireflyIII\TransactionRules\Actions\AppendDescription::act()
     */
    public function testActExistingTag()
    {
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'APPEND';

        $journal        = TransactionJournal::find(1);
        $oldDescription = $journal->description;
        $action         = new AppendDescription($ruleAction);
        $result         = $action->act($journal);
        $this->assertTrue($result);

        $journal = TransactionJournal::find(1);
        $this->assertEquals($oldDescription . 'APPEND', $journal->description);

    }

}