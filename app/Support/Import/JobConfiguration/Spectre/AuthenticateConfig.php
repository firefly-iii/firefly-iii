<?php
/**
 * AuthenticateConfig.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Spectre;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Token;
use Illuminate\Support\MessageBag;

/**
 * Class AuthenticateConfig
 *
 * @package FireflyIII\Support\Import\JobConfiguration\Spectre
 */
class AuthenticateConfig implements SpectreJobConfig
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Return true when this stage is complete.
     *
     * always returns false.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        return false;
    }

    /**
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        // does nothing
        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        // next data only makes sure the job is ready for the next stage.
        $this->repository->setStatus($this->importJob, 'ready_to_run');
        $this->repository->setStage($this->importJob, 'authenticated');
        $config = $this->importJob->configuration;
        $token  = isset($config['token']) ? new Token($config['token']) : null;
        if (null !== $token) {
            return ['token-url' => $token->getConnectUrl()];
        }
        throw new FireflyException('The import routine cannot continue without a Spectre token. Apologies.');
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.spectre.redirect';
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}