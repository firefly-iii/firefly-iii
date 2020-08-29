<?php
/**
 * SetDescriptionTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\Models\RuleAction;
use FireflyIII\TransactionRules\Actions\SetDescription;
use Tests\TestCase;

/**
 * Class SetDescriptionTest
 */
class SetDescriptionTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetDescription
     */
    public function testAct(): void
    {
        $withdrawal     = $this->getRandomWithdrawal();
        $newDescription = sprintf('new description #%d', $this->randomInt());
        $oldDescription = $withdrawal->description;

        // get journal, give fixed description
        $array = [
            'description'            => $oldDescription,
            'transaction_journal_id' => $withdrawal->id,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newDescription;
        $action                   = new SetDescription($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        $withdrawal->refresh();

        // assert result
        $this->assertEquals($newDescription, $withdrawal->description);

        $withdrawal->description = $oldDescription;
        $withdrawal->save();
    }
}
