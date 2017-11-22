<?php
/**
 * PrependDescriptionTest.php
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
use FireflyIII\TransactionRules\Actions\PrependDescription;
use Tests\TestCase;

/**
 * Class PrependDescriptionTest
 */
class PrependDescriptionTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\PrependDescription::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\PrependDescription::act()
     */
    public function testAct()
    {
        // get journal, give fixed description
        $description          = 'text' . rand(1, 1000);
        $prepend              = 'prepend' . rand(1, 1234);
        $journal              = TransactionJournal::find(7);
        $journal->description = $description;
        $journal->save();

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $prepend;
        $action                   = new PrependDescription($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $journal = TransactionJournal::find(7);

        // assert result
        $this->assertEquals($prepend . $description, $journal->description);
    }
}
