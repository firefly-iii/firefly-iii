<?php

/**
 * CreateImport.php
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

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Internal\File\EncryptService;
use Illuminate\Console\Command;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * Class CreateImport.
 */
class CreateImport extends Command
{
    use VerifiesAccessToken;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to create a new import. Your user ID can be found on the /profile page.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature
        = 'firefly:create-import
                            {file : The file to import.}
                            {configuration : The configuration file to use for the import.}
                            {--type=csv : The file type of the import.}
                            {--user= : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}
                            {--start : Starts the job immediately.}';

    /**
     * Run the command.
     *
     * @noinspection MultipleReturnStatementsInspection
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->errorLine('Invalid access token.');

            return 1;
        }
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);
        $file           = $this->argument('file');
        $configuration  = $this->argument('configuration');
        $user           = $userRepository->findNull((int)$this->option('user'));
        $cwd            = getcwd();
        $type           = strtolower($this->option('type'));

        if (!$this->validArguments()) {
            $this->errorLine('Invalid arguments.');

            return 1;
        }

        $configurationData = json_decode(file_get_contents($configuration), true);
        if (null === $configurationData) {
            $this->errorLine(sprintf('Firefly III cannot read the contents of configuration file "%s" (working directory: "%s").', $configuration, $cwd));

            return 1;
        }

        $this->infoLine(sprintf('Going to create a job to import file: %s', $file));
        $this->infoLine(sprintf('Using configuration file: %s', $configuration));
        $this->infoLine(sprintf('Import into user: #%d (%s)', $user->id, $user->email));
        $this->infoLine(sprintf('Type of import: %s', $type));

        /** @var ImportJobRepositoryInterface $jobRepository */
        $jobRepository = app(ImportJobRepositoryInterface::class);
        $jobRepository->setUser($user);
        $job = $jobRepository->create($type);
        $this->infoLine(sprintf('Created job "%s"', $job->key));

        /** @var EncryptService $service */
        $service = app(EncryptService::class);
        $service->encrypt($file, $job->key);

        $this->infoLine('Stored import data...');

        $jobRepository->setConfiguration($job, $configurationData);
        $jobRepository->updateStatus($job, 'configured');
        $this->infoLine('Stored configuration...');

        if (true === $this->option('start')) {
            $this->infoLine('The import will start in a moment. This process is not visible...');
            Log::debug('Go for import!');

            // normally would refer to other firefly:start-import but that doesn't seem to work all to well...

            // start the actual routine:
            $type      = 'csv' === $job->file_type ? 'file' : $job->file_type;
            $key       = sprintf('import.routine.%s', $type);
            $className = config($key);
            if (null === $className || !class_exists($className)) {
                throw new FireflyException(sprintf('Cannot find import routine class for job of type "%s".', $type)); // @codeCoverageIgnore
            }
            /** @var RoutineInterface $routine */
            $routine = app($className);
            $routine->setJob($job);
            $routine->run();

            // give feedback.
            /** @var MessageBag $error */
            foreach ($routine->getErrors() as $index => $error) {
                $this->errorLine(sprintf('Error importing line #%d: %s', $index, $error));
            }
            $this->infoLine(
                sprintf(
                    'The import has finished. %d transactions have been imported out of %d records.', $routine->getJournals()->count(), $routine->getLines()
                )
            );
        }

        // clear cache for user:
        Preferences::setForUser($user, 'lastActivity', microtime());

        return 0;
    }

    /**
     * @param string     $message
     * @param array|null $data
     */
    private function errorLine(string $message, array $data = null): void
    {
        Log::error($message, $data ?? []);
        $this->error($message);

    }

    /**
     * @param string $message
     * @param array  $data
     */
    private function infoLine(string $message, array $data = null): void
    {
        Log::info($message, $data ?? []);
        $this->line($message);
    }

    /**
     * Verify user inserts correct arguments.
     *
     * @noinspection MultipleReturnStatementsInspection
     * @return bool
     */
    private function validArguments(): bool
    {
        $file          = $this->argument('file');
        $configuration = $this->argument('configuration');
        $cwd           = getcwd();
        $validTypes    = config('import.options.file.import_formats');
        $type          = strtolower($this->option('type'));

        if (!\in_array($type, $validTypes, true)) {
            $this->errorLine(sprintf('Cannot import file of type "%s"', $type));

            return false;
        }

        if (!file_exists($file)) {
            $this->errorLine(sprintf('Firefly III cannot find file "%s" (working directory: "%s").', $file, $cwd));

            return false;
        }

        if (!file_exists($configuration)) {
            $this->errorLine(sprintf('Firefly III cannot find configuration file "%s" (working directory: "%s").', $configuration, $cwd));

            return false;
        }

        return true;
    }
}
