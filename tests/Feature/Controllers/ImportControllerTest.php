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
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
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
     * @covers \FireflyIII\Http\Controllers\ImportController::complete
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testCompleteWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.complete', ['configure']));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::configure
     * @covers \FireflyIII\Http\Controllers\ImportController::makeImporter
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
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
     * @covers \FireflyIII\Http\Controllers\ImportController::configure
     * @covers \FireflyIII\Http\Controllers\ImportController::makeImporter
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testConfigureWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.configure', ['settings']));
        $response->assertStatus(302);
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
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
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
     * @covers \FireflyIII\Http\Controllers\ImportController::finished
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testFinishedWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.finished', ['configure']));
        $response->assertStatus(302);
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
     * @covers \FireflyIII\Http\Controllers\ImportController::json
     */
    public function testJsonFinished()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $tag          = factory(Tag::class)->make();

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $tagRepos->shouldReceive('find')->once()->andReturn($tag);

        $this->be($this->user());
        $response = $this->get(route('import.json', ['finished']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::json
     */
    public function testJsonRunning()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.json', ['running']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postConfigure
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testPostConfigure()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $importer = $this->mock(CsvSetup::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('saveImportConfiguration')->once();
        $repository->shouldReceive('updateStatus')->once();

        $data = [];
        $this->be($this->user());
        $response = $this->post(route('import.process-configuration', ['p-configure']), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.settings', ['p-configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postConfigure
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testPostConfigureWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [];
        $this->be($this->user());
        $response = $this->post(route('import.process-configuration', ['finished']), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.finished', ['finished']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postSettings
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
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
     * @covers \FireflyIII\Http\Controllers\ImportController::postSettings
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testPostSettingsWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [];
        $this->be($this->user());
        $response = $this->post(route('import.post-settings', ['configure']), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::settings
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testSettings()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $importer = $this->mock(CsvSetup::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('requireUserSettings')->once()->andReturn(false);
        $repository->shouldReceive('updateStatus')->once();

        $this->be($this->user());
        $response = $this->get(route('import.settings', ['settings']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.complete', ['settings']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::settings
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testSettingsUserSettings()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $importer = $this->mock(CsvSetup::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('requireUserSettings')->once()->andReturn(true);

        $importer->shouldReceive('getDataForSettings')->once()->andReturn([]);
        $importer->shouldReceive('getViewForSettings')->once()->andReturn('error');

        $this->be($this->user());
        $response = $this->get(route('import.settings', ['settings']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::settings
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testSettingsWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.settings', ['configure']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::settings
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testSettingsWrongJobAgain()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('import.settings', ['complete']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.complete', ['complete']));
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
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
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
     * @covers \FireflyIII\Http\Controllers\ImportController::status
     * @covers \FireflyIII\Http\Controllers\ImportController::jobInCorrectStep
     * @covers \FireflyIII\Http\Controllers\ImportController::redirectToCorrectStep
     */
    public function testStatusWrongJob()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        // complete
        $this->be($this->user());
        $response = $this->get(route('import.status', ['configure']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::upload
     */
    public function testUpload()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $job          = factory(ImportJob::class)->make();

        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->andReturn(false);
        $repository->shouldReceive('create')->andReturn($job);

        $path = resource_path('stubs/csv.csv');
        $file = new UploadedFile($path, 'upload.csv', filesize($path), 'text/csv', null, true);
        $this->be($this->user());
        $response = $this->post(route('import.upload'), ['import_file_type' => 'csv', 'import_file' => $file]);

        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::upload
     */
    public function testUploadDemo()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $job          = factory(ImportJob::class)->make();

        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->andReturn(true);
        $repository->shouldReceive('create')->andReturn($job);
        $repository->shouldReceive('setConfiguration')->andReturn($job);

        $path = resource_path('stubs/csv.csv');
        $file = new UploadedFile($path, 'upload.csv', filesize($path), 'text/csv', null, true);
        $this->be($this->user());
        $response = $this->post(route('import.upload'), ['import_file_type' => 'csv', 'import_file' => $file]);

        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::upload
     */
    public function testUploadWithConfig()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $job          = factory(ImportJob::class)->make();

        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->andReturn(false);
        $repository->shouldReceive('create')->andReturn($job);

        $path       = resource_path('stubs/csv.csv');
        $file       = new UploadedFile($path, 'upload.csv', filesize($path), 'text/csv', null, true);
        $configPath = resource_path('stubs/demo-configuration.json');
        $configFile = new UploadedFile($path, 'configuration.json', filesize($configPath), 'application/json', null, true);
        $this->be($this->user());
        $response = $this->post(route('import.upload'), ['import_file_type' => 'csv', 'import_file' => $file, 'configuration_file' => $configFile]);

        $response->assertStatus(302);
    }
}
