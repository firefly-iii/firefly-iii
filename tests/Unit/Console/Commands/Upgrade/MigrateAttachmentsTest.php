<?php
declare(strict_types=1);
/**
 * MigrateAttachmentsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests\Unit\Console\Commands\Upgrade;


use FireflyConfig;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

/**
 * Class MigrateAttachmentsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MigrateAttachmentsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateAttachments
     */
    public function testHandle(): void
    {
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_migrate_attachments', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_migrate_attachments', true]);
        // assume all is well.
        $this->artisan('firefly-iii:migrate-attachments')
             ->expectsOutput('All attachments are OK.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateAttachments
     */
    public function testHandleMigrate(): void
    {
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_migrate_attachments', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_migrate_attachments', true]);

        $attachment = Attachment::create(
            [
                'user_id'         => 1,
                'attachable_id'   => 1,
                'attachable_type' => TransactionJournal::class,
                'description'     => 'Hello',
                'md5'             => md5('hello'),
                'filename'        => 'test.pdf',
                'mime'            => 'text/plain',
                'size'            => 1,
            ]);

        // assume all is well.
        $this->artisan('firefly-iii:migrate-attachments')
             ->expectsOutput('Updated 1 attachment(s).')
             ->assertExitCode(0);

        $this->assertCount(0, Attachment::where('id', $attachment->id)->where('description', '!=', '')->get());
        $this->assertCount(1, Attachment::where('id', $attachment->id)->where('description', '=', '')->get());
        $this->assertCount(1, Note::where('noteable_id', $attachment->id)->where('noteable_type', Attachment::class)->get());

        $attachment->forceDelete();
    }

}
