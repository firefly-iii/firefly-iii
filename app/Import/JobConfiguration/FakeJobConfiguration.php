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
class FakeJobConfiguration implements JobConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;

    /** @var ImportJobRepositoryInterface */
    private $repository;

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
        // if stage is not "new", then album must be 'station to station'
        $config = $this->importJob->configuration;
        if ($this->importJob->stage === 'new') {
            return (isset($config['artist']) && 'david bowie' === strtolower($config['artist']))
                   && (isset($config['song']) && 'golden years' === strtolower($config['song']))
                   && isset($config['apply-rules']);
        }

        return isset($config['album']) && 'station to station' === strtolower($config['album']);


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
        $song          = strtolower($data['song'] ?? '');
        $album         = strtolower($data['album'] ?? '');
        $applyRules    = isset($data['apply_rules']) ? 1 === (int)$data['apply_rules'] : null;
        $configuration = $this->importJob->configuration;
        if ('david bowie' === $artist) {
            // store artist
            $configuration['artist'] = $artist;
        }

        if ($song === 'golden years') {
            // store song
            $configuration['song'] = $song;
        }

        if ($album === 'station to station') {
            // store album
            $configuration['album'] = $album;
        }
        if (null !== $applyRules) {
            $configuration['apply-rules'] = $applyRules;
        }

        $this->repository->setConfiguration($this->importJob, $configuration);
        $messages = new MessageBag();

        if (\count($configuration) !== 3) {

            $messages->add('some_key', 'Ignore this error: ' . \count($configuration));
        }

        return $messages;
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function getNextData(): array
    {
        return [
            'rulesOptions' => [
                1 => (string)trans('firefly.yes'),
                0 => (string)trans('firefly.no'),
            ],
        ];
    }

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     */
    public function getNextView(): string
    {
        // first configure artist:
        $config     = $this->importJob->configuration;
        $artist     = $config['artist'] ?? '';
        $song       = $config['song'] ?? '';
        $album      = $config['album'] ?? '';
        $applyRules = $config['apply-rules'] ?? null;
        if (null === $applyRules) {
            return 'import.fake.apply-rules';
        }
        if (strtolower($artist) !== 'david bowie') {
            return 'import.fake.enter-artist';
        }
        if (strtolower($song) !== 'golden years') {
            return 'import.fake.enter-song';
        }
        if (strtolower($album) !== 'station to station' && $this->importJob->stage !== 'new') {
            return 'import.fake.enter-album';
        }

        return 'impossible-view'; // @codeCoverageIgnore
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}
