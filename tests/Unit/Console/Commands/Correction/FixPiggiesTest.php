<?php
/**
 * FixPiggiesTest.php
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use Log;
use Tests\TestCase;

/**
 * Class FixPiggiesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FixPiggiesTest extends TestCase
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
     * Null event.
     *
     * @covers \FireflyIII\Console\Commands\Correction\FixPiggies
     */
    public function testHandleNull(): void
    {
        /** @var PiggyBank $piggy */
        $piggy = $this->user()->piggyBanks()->inRandomOrder()->first();

        // create event to trigger console commands.
        $event = PiggyBankEvent::create(
            [
                'piggy_bank_id' => $piggy->id,
                'date'          => '2019-01-01',
                'amount'        => 5,
            ]
        );

        // assume there's nothing to fix.
        $this->artisan('firefly-iii:fix-piggies')
             ->expectsOutput('All piggy bank events are correct.')
             ->assertExitCode(0);
        $event->forceDelete();
    }

    /**
     * Withdrawal instead of transfer
     *
     * @covers \FireflyIII\Console\Commands\Correction\FixPiggies
     */
    public function testHandleBadJournal(): void
    {
        /** @var PiggyBank $piggy */
        $piggy      = $this->user()->piggyBanks()->inRandomOrder()->first();
        $withdrawal = $this->getRandomWithdrawal();
        // create event to trigger console commands.
        $event = PiggyBankEvent::create(
            [
                'piggy_bank_id'          => $piggy->id,
                'date'                   => '2019-01-01',
                'amount'                 => 5,
                'transaction_journal_id' => $withdrawal->id,
            ]
        );

        // assume there's nothing to fix.
        $this->artisan('firefly-iii:fix-piggies')
             ->expectsOutput(sprintf('Piggy bank #%d was referenced by an invalid event. This has been fixed.', $piggy->id))
             ->expectsOutput('Fixed 1 piggy bank event(s).')
             ->assertExitCode(0);

        // verify update
        $this->assertCount(0, PiggyBankEvent::where('id', $event->id)->where('transaction_journal_id', $withdrawal->id)->get());
    }

    /**
     * Withdrawal instead of transfer
     *
     * @covers \FireflyIII\Console\Commands\Correction\FixPiggies
     */
    public function testHandleDeletedJournal(): void
    {
        /** @var PiggyBank $piggy */
        $piggy                = $this->user()->piggyBanks()->inRandomOrder()->first();
        $transfer             = $this->getRandomTransfer();
        $event                = PiggyBankEvent::create(
            [
                'piggy_bank_id'          => $piggy->id,
                'date'                   => '2019-01-01',
                'amount'                 => 5,
                'transaction_journal_id' => $transfer->id,
            ]
        );
        $transfer->deleted_at = '2019-01-01 12:00:00';
        $transfer->save();
        $transfer->refresh();

        $this->artisan('firefly-iii:fix-piggies')
             ->expectsOutput('Fixed 1 piggy bank event(s).')
             ->assertExitCode(0);

        // verify update
        $this->assertCount(0, PiggyBankEvent::where('id', $event->id)->where('transaction_journal_id', $transfer->id)->get());
        $event->forceDelete();
    }
}
