<?php
/**
 * AttachmentHelperTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use FireflyIII\Helpers\Attachments\AttachmentHelper;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class AttachmentHelperTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttachmentHelperTest extends TestCase
{
    /**
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::getAttachmentLocation
     */
    public function testGetAttachmentLocation()
    {
        $attachment = Attachment::first();
        $helper     = new AttachmentHelper;
        $path       = $path = sprintf('%s%sat-%d.data', storage_path('upload'), DIRECTORY_SEPARATOR, intval($attachment->id));
        $this->assertEquals($helper->getAttachmentLocation($attachment), $path);
    }

    /**
     * Test invalid mime thing
     *
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::__construct
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::saveAttachmentsForModel
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::processFile
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::validateUpload
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::validMime
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::hasFile
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::getMessages
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::getErrors
     */
    public function testInvalidMime()
    {
        $journal = TransactionJournal::first();
        $helper  = new AttachmentHelper;
        $path    = resource_path('stubs/binary.bin');
        $file    = new UploadedFile($path, 'binary.bin', 'application/octet-stream', filesize($path), null, true);

        $helper->saveAttachmentsForModel($journal, [$file]);
        $errors   = $helper->getErrors();
        $messages = $helper->getMessages();

        $this->assertCount(1, $errors);
        $this->assertCount(0, $messages);
        $this->assertEquals('File "binary.bin" is of type "application/octet-stream" which is not accepted as a new upload.', $errors->first());
    }

    /**
     * Test valid file upload.
     *
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::__construct
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::saveAttachmentsForModel
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::processFile
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::validateUpload
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::validMime
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::hasFile
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::getMessages
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::getErrors
     * @covers \FireflyIII\Helpers\Attachments\AttachmentHelper::getAttachments
     */
    public function testSave()
    {
        Storage::fake('upload');

        $journal = TransactionJournal::first();
        $helper  = new AttachmentHelper;
        $path    = public_path('apple-touch-icon.png');
        $file    = new UploadedFile($path, 'apple-touch-icon.png', 'image/png', filesize($path), null, true);

        $helper->saveAttachmentsForModel($journal, [$file]);
        $errors      = $helper->getErrors();
        $messages    = $helper->getMessages();
        $attachments = $helper->getAttachments();

        $this->assertCount(0, $errors);
        $this->assertCount(1, $messages);
        $this->assertEquals('Succesfully uploaded file "apple-touch-icon.png".', $messages->first());

        // Assert the file was stored...
        Storage::disk('upload')->assertExists(sprintf('at-%d.data', $attachments->first()->id));
    }

    /**
     * Test double file upload. Needs to be after testSave.
     *
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::__construct
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::saveAttachmentsForModel
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::processFile
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::validateUpload
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::validMime
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::hasFile
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::getMessages
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::getErrors
     * @covers  \FireflyIII\Helpers\Attachments\AttachmentHelper::getAttachments
     */
    public function testSaveSecond()
    {
        $journal = TransactionJournal::first();
        $helper  = new AttachmentHelper;
        $path    = public_path('apple-touch-icon.png');
        $file    = new UploadedFile($path, 'apple-touch-icon.png', 'image/png', filesize($path), null, true);

        $helper->saveAttachmentsForModel($journal, [$file]);
        $errors   = $helper->getErrors();
        $messages = $helper->getMessages();

        $this->assertCount(1, $errors);
        $this->assertCount(0, $messages);
        $this->assertEquals('Uploaded file "apple-touch-icon.png" is already attached to this object.', $errors->first());
    }
}
