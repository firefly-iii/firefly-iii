<?php
/**
 * FakeJobConfiguration.php
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

namespace FireflyIII\Import\JobConfiguration;

use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;

/**
 * Class FakeJobConfiguration
 */
class FakeJobConfiguration implements JobConfiguratorInterface
{
    /** @var ImportJob */
    private $job;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * ConfiguratorInterface constructor.
     */
    public function __construct()
    {
        $this->repository = app(ImportJobRepositoryInterface::class);
    }

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        // configuration array of job must have two values:
        // 'artist' must be 'david bowie', case insensitive
        // 'song' must be 'golden years', case insensitive.
        $config = $this->job->configuration;

        return (isset($config['artist']) && 'david bowie' === strtolower($config['artist']))
               && (isset($config['song']) && 'golden years' === strtolower($config['song']));
    }

    /**
     * Store any data from the $data array into the job.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        $artist        = strtolower($data['artist'] ?? '');
        $configuration = $this->job->configuration;
        if ($artist === 'david bowie') {
            // store artist
            $configuration['artist'] = $artist;
        }
        $song = strtolower($data['song'] ?? '');
        if ($song === 'golden years') {
            // store artist
            $configuration['song'] = $song;
        }
        $this->repository->setConfiguration($this->job, $configuration);
        $messages = new MessageBag();

        if (\count($configuration) !== 2) {

            $messages->add('some_key', 'Ignore this error');
        }

        return $messages;
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     */
    public function getNextData(): array
    {
        return [];
    }

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     */
    public function getNextView(): string
    {
        // first configure artist:
        $config = $this->job->configuration;
        $artist = $config['artist'] ?? '';
        $song   = $config['song'] ?? '';
        if (strtolower($artist) !== 'david bowie') {
            return 'import.fake.enter-artist';
        }
        if (strtolower($song) !== 'golden years') {
            return 'import.fake.enter-song';
        }
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->job = $job;
        $this->repository->setUser($job->user);
    }
}