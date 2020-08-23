<?php
/**
 * PrependDescriptionTest.php
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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\AppendDescription;
use FireflyIII\TransactionRules\Actions\PrependDescription;
use Tests\TestCase;

/**
 * Class PrependDescriptionTest
 */
class PrependDescriptionTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\PrependDescription
     */
    public function testAct(): void
    {
        /** @var TransactionJournal $journal */
        $journal  = $this->user()->transactionJournals()->where('description', 'Rule action test transaction.')->first();
        $original = $journal->description;


        $array = [
            'transaction_journal_id' => $journal->id,
            'description'            => $original,
        ];

        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'PREPEND';
        $action                   = new PrependDescription($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        $journal = TransactionJournal::find($journal->id);
        $this->assertEquals(sprintf('%s%s', $ruleAction->action_value, $original), $journal->description);

        $journal->description = $original;
        $journal->save();
    }
}
