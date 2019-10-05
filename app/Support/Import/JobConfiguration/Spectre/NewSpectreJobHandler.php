<?php
/**
 * NewSpectreJobHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Spectre;


use FireflyIII\Models\ImportJob;
use Illuminate\Support\MessageBag;
use Log;

/**
 * @codeCoverageIgnore
 *
 * Class NewSpectreJobHandler
 *
 */
class NewSpectreJobHandler implements SpectreJobConfigurationInterface
{

    /**
     * Return true when this stage is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        Log::debug('NewSpectreJobHandler::configurationComplete() always returns true');

        return true;
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
        Log::debug('NewSpectreJobHandler::configureJob() always returns an empty message bag');

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     */
    public function getNextData(): array
    {
        Log::debug('NewSpectreJobHandler::getNextData() always returns []');

        return [];
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('NewSpectreJobHandler::getNextView() always returns ""');

        return '';
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
    }
}
