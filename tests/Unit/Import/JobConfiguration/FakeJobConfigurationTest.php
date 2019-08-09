<?php
/**
 * FakeJobConfigurationTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace Tests\Unit\Import\JobConfiguration;

use FireflyIII\Import\JobConfiguration\FakeJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class FakeJobConfigurationTest
 */
class FakeJobConfigurationTest extends TestCase
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
     * No config, job is new.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCC(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'A_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();


        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * No config, job is not new.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCCAlbumFalse(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'B_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'needs_config';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * Job only says to apply rules.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCCApplyRules(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'C_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'apply-rules' => true,
        ];
        $job->save();

        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * Job has album but wrong one.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCCBadAlbum(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'D_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'config';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'song'        => 'golden years',
            'artist'      => 'david bowie',
            'album'       => 'some album',
            'apply-rules' => true,
        ];
        $job->save();

        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * Job has album + song, but bad content.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCCBadInfo(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'E_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'song'        => 'some song',
            'artist'      => 'david bowie',
            'apply-rules' => true,
        ];
        $job->save();

        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * Job has correct album
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCCGoodAlbum(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'f_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'config';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'song'        => 'golden years',
            'artist'      => 'david bowie',
            'album'       => 'station to station',
            'apply-rules' => true,
        ];
        $job->save();

        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertTrue($configurator->configurationComplete());
    }

    /**
     * Job has correct content for "new"!
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testCCGoodNewInfo(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'g_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'song'        => 'golden years',
            'artist'      => 'david bowie',
            'apply-rules' => true,
        ];
        $job->save();

        // should be false:
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertTrue($configurator->configurationComplete());
    }

    /**
     * Apply rules with submitted "false"
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobARFalse(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'h_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['apply_rules' => 0];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), ['apply-rules' => false]])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Apply rules with submitted "false"
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobARTrue(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'i_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['apply_rules' => 1];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), ['apply-rules' => true]])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Submit job with bad song.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobBadAlbum(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'j_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['album' => 'Station to Bowie'];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), []])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Submit job with bad artist.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobBadArtist(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'k_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['artist' => 'DaViD BoWXXXXXiE'];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), []])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Submit job with bad song.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobBadSong(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'l_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['song' => 'Golden Bowie'];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), []])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Submit job with good album.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobGoodAlbum(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'm_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['album' => 'Station to Station'];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), ['album' => 'station to station']])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Submit job with good artist.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobGoodArtist(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'n_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['artist' => 'DaViD BoWiE'];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), ['artist' => 'david bowie']])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Submit job with good song.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testConfigureJobGoodSong(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'o_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // data to submit:
        $data = ['song' => 'Golden Years'];

        // expect the config to update:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), ['song' => 'golden years']])->once();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $messages = $configurator->configureJob($data);
        $this->assertTrue($messages->has('some_key'));
    }

    /**
     * Have rules, have artist, have song, must ask album
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testGetNextViewAlbum(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'p_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'not_new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => false, 'artist' => 'david bowie', 'song' => 'golden years'];
        $job->save();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $view = $configurator->getNextView();
        $this->assertEquals('import.fake.enter-album', $view);
    }

    /**
     * Have rules, must ask artist
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testGetNextViewArtist(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'p_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => false];
        $job->save();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $view = $configurator->getNextView();
        $this->assertEquals('import.fake.enter-artist', $view);
    }

    /**
     * With empty config, should return view for rules.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testGetNextViewRules(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'p_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $view = $configurator->getNextView();
        $this->assertEquals('import.fake.apply-rules', $view);
    }

    /**
     * Have rules, have artist, must ask song
     *
     * @covers \FireflyIII\Import\JobConfiguration\FakeJobConfiguration
     */
    public function testGetNextViewSong(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'p_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => false, 'artist' => 'david bowie'];
        $job->save();

        // call configuration
        $configurator = new FakeJobConfiguration;
        $configurator->setImportJob($job);
        $view = $configurator->getNextView();
        $this->assertEquals('import.fake.enter-song', $view);
    }
}
