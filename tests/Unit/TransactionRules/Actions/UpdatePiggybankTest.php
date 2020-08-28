<?php
/*
 * UpdatePiggybankTest.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace Tests\Unit\TransactionRules\Actions;


use FireflyIII\Models\RuleAction;
use FireflyIII\TransactionRules\Actions\UpdatePiggybank;
use Log;
use Tests\TestCase;

class UpdatePiggybankTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Actions\UpdatePiggybank
     */
    public function testActOnArraySource(): void
    {
        // from saving to checking
        $transfer = $this->user()->transactionJournals()->where('description', 'Transfer for piggy bank 1')->first();
        // update (the only) piggy to belong to source:
        $piggy = $this->user()->piggyBanks()->where('piggy_banks.name', 'Action test 1')->first();


        $array = [
            'transaction_journal_id' => $transfer->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type'  => 'Transfer',
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $piggy->name;
        $action                   = new UpdatePiggybank($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\UpdatePiggybank
     */
    public function testActOnArrayDestination(): void
    {
        // from saving to checking
        $transfer = $this->user()->transactionJournals()->where('description', 'Transfer for piggy bank 2')->first();
        // update (the only) piggy to belong to source:
        $piggy = $this->user()->piggyBanks()->where('piggy_banks.name', 'Action test 1')->first();


        $array = [
            'transaction_journal_id' => $transfer->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type'  => 'Transfer',
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $piggy->name;
        $action                   = new UpdatePiggybank($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);
    }
}