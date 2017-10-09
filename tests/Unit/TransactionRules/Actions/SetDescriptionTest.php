<?php
/**
 * SetDescriptionTest.php
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
use FireflyIII\TransactionRules\Actions\SetDescription;
use Tests\TestCase;

/**
 * Class SetDescriptionTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class SetDescriptionTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetDescription::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetDescription::act()
     */
    public function testAct()
    {
        // get journal, give fixed description
        $description          = 'text' . rand(1, 1000);
        $newDescription       = 'new description' . rand(1, 1234);
        $journal              = TransactionJournal::find(14);
        $journal->description = $description;
        $journal->save();

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newDescription;
        $action                   = new SetDescription($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $journal = TransactionJournal::find(14);

        // assert result
        $this->assertEquals($newDescription, $journal->description);

    }
}