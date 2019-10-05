<?php
/**
 * RenameMetaFieldsTest.php
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


use FireflyIII\Models\TransactionJournalMeta;
use Log;
use Tests\TestCase;

/**
 * Class RenameMetaFieldsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RenameMetaFieldsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\RenameMetaFields
     */
    public function testHandle(): void
    {
        $this->artisan('firefly-iii:rename-meta-fields')
             ->expectsOutput('All meta fields are correct.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\RenameMetaFields
     */
    public function testHandleFixed(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $entry      = TransactionJournalMeta::create(
            [
                'transaction_journal_id' => $withdrawal->id,
                'name'                   => 'importHashV2',
                'data'                   => 'Fake data',

            ]
        );

        $this->artisan('firefly-iii:rename-meta-fields')
             ->expectsOutput('Renamed 1 meta field(s).')
             ->assertExitCode(0);

        // verify update
        $this->assertCount(1, TransactionJournalMeta::where('id', $entry->id)->where('name', 'import_hash_v2')->get());
    }

}
