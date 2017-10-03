<?php
/**
 * AppendDescriptionTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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