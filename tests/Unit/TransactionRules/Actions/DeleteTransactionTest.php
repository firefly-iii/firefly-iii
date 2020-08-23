<?php
/**
 * DeleteTransactionTest.php
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

use Exception;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\DeleteTransaction;
use Log;
use Tests\TestCase;

/**
 * Class AddTagTest
 */
class DeleteTransactionTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Actions\DeleteTransaction
     */
    public function testAct(): void
    {
        $journal = TransactionJournal::whereDescription('Withdrawal to DELETE.')->first();


        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new DeleteTransaction($ruleAction);

        $array = [
            'transaction_journal_id' => $journal->id,
            'transaction_group_id'   => $journal->transaction_group_id,
            'description'            => $journal->description,
        ];

        try {
            $result = $action->actOnArray($array);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // assert result
        $journal->refresh();
        $this->assertNotNull($journal->deleted_at);
    }


}
