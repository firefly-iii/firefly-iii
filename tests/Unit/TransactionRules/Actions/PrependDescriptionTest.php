<?php
/**
 * PrependDescriptionTest.php
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
use FireflyIII\TransactionRules\Actions\PrependDescription;
use Tests\TestCase;

/**
 * Class PrependDescriptionTest
 *
 * @package Tests\Unit\TransactionRules\Actions
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
        $journal              = TransactionJournal::find(7);

        // assert result
        $this->assertEquals($prepend.$description, $journal->description);

    }
}