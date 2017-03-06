<?php
/**
 * ImportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Import\ImportProcedureInterface;
use FireflyIII\Import\Setup\CsvSetup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Class ImportControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class ImportControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::complete
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testComplete()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.complete', ['complete']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::configure
     * @covers \FireflyIII\Http\Controllers\ImportController::makeImporter
     */
    public function testConfigure()
    {
        $setup        = $this->mock(CsvSetup::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $setup->shouldReceive('setJob')->once();
        $setup->shouldReceive('configure')->once();
        $setup->shouldReceive('getConfigurationData')->andReturn(['specifics' => [], 'delimiters' => [], 'accounts' => []])->once();

        $this->be($this->user());
        $response = $this->get(route('import.configure', ['configure']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::download
     */
    public function testDownload()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.download', ['configure']));
        $response->assertStatus(200);
        $response->assertJson(
            [
                'delimiter'               => 'tab',
                'column-roles-complete'   => false,
                'column-mapping-complete' => false,
            ]
        );
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::finished
     */
    public function testFinished()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.finished', ['finished']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::index
     * @covers \FireflyIII\Http\Controllers\ImportController::__construct
     */
    public function testIndex()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::json
     */
    public function testJson()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.json', ['configure']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postConfigure
     */
    public function testPostConfigure()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $importer = $this->mock(CsvSetup::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('saveImportConfiguration')->once();

        $data = [];
        $this->be($this->user());
        $response = $this->post(route('import.process-configuration', ['p-configure']), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.settings', ['p-configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postSettings
     */
    public function testPostSettings()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $importer = $this->mock(CsvSetup::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('storeSettings')->once();
        $data = [];
        $this->be($this->user());
        $response = $this->post(route('import.post-settings', ['p-settings']), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.settings', ['p-settings']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::settings
     */
    public function testSettings()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $importer = $this->mock(CsvSetup::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('requireUserSettings')->once()->andReturn(false);
        $this->be($this->user());
        $response = $this->get(route('import.settings', ['settings']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.complete', ['settings']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::start
     */
    public function testStart()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        /** @var ImportProcedureInterface $procedure */
        $procedure = $this->mock(ImportProcedureInterface::class);

        $procedure->shouldReceive('runImport');

        $this->be($this->user());
        $response = $this->post(route('import.start', ['complete']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::status
     * Implement testStatus().
     */
    public function testStatus()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        // complete
        $this->be($this->user());
        $response = $this->get(route('import.status', ['complete']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::upload
     */
    public function testUpload()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $path     = resource_path('stubs/csv.csv');
        $file     = new UploadedFile($path, 'upload.csv', filesize($path), 'text/csv', null, true);
        $response = $this->post(route('import.upload'), [], [], ['import_file' => $file], ['Accept' => 'application/json']);
        $response->assertStatus(302);
    }

}
