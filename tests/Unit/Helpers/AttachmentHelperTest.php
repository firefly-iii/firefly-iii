<?php
/**
 * AttachmentHelperTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * @package Tests\Unit\Helpers
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
     *
     */
    public function testInvalidMime()
    {
        $journal = TransactionJournal::first();
        $helper  = new AttachmentHelper;
        $path    = resource_path('stubs/csv.csv');
        $file    = new UploadedFile($path, 'csv.csv', 'text/plain', filesize($path), null, true);

        $helper->saveAttachmentsForModel($journal, [$file]);
        $errors   = $helper->getErrors();
        $messages = $helper->getMessages();

        $this->assertCount(1, $errors);
        $this->assertCount(0, $messages);
        $this->assertEquals('File "csv.csv" is of type "text/plain" which is not accepted as a new upload.', $errors->first());
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
     *
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
     *
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
