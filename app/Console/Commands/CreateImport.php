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

/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Console\Command;
use Log;

/**
 * Class CreateImport.
 *
 * @codeCoverageIgnore
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
                            {file? : The file to import.}
                            {configuration? : The configuration file to use for the import.}
                            {--type=csv : The file type of the import.}
                            {--provider=file : The file type of the import.}
                            {--user=1 : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}
                            {--start : Starts the job immediately.}';

    /**
     * Run the command.
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
        $userRepository    = app(UserRepositoryInterface::class);
        $file              = (string)$this->argument('file');
        $configuration     = (string)$this->argument('configuration');
        $user              = $userRepository->findNull((int)$this->option('user'));
        $cwd               = getcwd();
        $provider          = strtolower((string)$this->option('provider'));
        $configurationData = [];

        if (null === $user) {
            $this->errorLine('User is NULL.');

            return 1;
        }

        if (!$this->validArguments()) {
            $this->errorLine('Invalid arguments.');

            return 1;
        }
        if (\strlen($configuration) > 0) {
            $configurationData = json_decode(file_get_contents($configuration), true);
            if (null === $configurationData) {
                $this->errorLine(sprintf('Firefly III cannot read the contents of configuration file "%s" (working directory: "%s").', $configuration, $cwd));

                return 1;
            }
        }


        $this->infoLine(sprintf('Going to create a job to import file: %s', $file));
        $this->infoLine(sprintf('Using configuration file: %s', $configuration));
        $this->infoLine(sprintf('Import into user: #%d (%s)', $user->id, $user->email));
        $this->infoLine(sprintf('Type of import: %s', $provider));

        /** @var ImportJobRepositoryInterface $jobRepository */
        $jobRepository = app(ImportJobRepositoryInterface::class);
        $jobRepository->setUser($user);
        $importJob = $jobRepository->create($provider);
        $this->infoLine(sprintf('Created job "%s"', $importJob->key));

        // make sure that job has no prerequisites.
        if ((bool)config(sprintf('import.has_prereq.%s', $provider))) {
            // make prerequisites thing.
            $class = (string)config(sprintf('import.prerequisites.%s', $provider));
            if (!class_exists($class)) {
                throw new FireflyException(sprintf('No class to handle prerequisites for "%s".', $provider)); // @codeCoverageIgnore
            }
            /** @var PrerequisitesInterface $object */
            $object = app($class);
            $object->setUser($user);
            if (!$object->isComplete()) {
                $this->errorLine(sprintf('Import provider "%s" has prerequisites that can only be filled in using the browser.', $provider));

                return 1;
            }
        }

        // store file as attachment.
        if (\strlen($file) > 0) {
            $messages = $jobRepository->storeCLIUpload($importJob, 'import_file', $file);
            if ($messages->count() > 0) {
                $this->errorLine($messages->first());

                return 1;
            }
            $this->infoLine('File content saved.');
        }

        $this->infoLine('Job configuration saved.');
        $jobRepository->setConfiguration($importJob, $configurationData);
        $jobRepository->setStatus($importJob, 'ready_to_run');


        if (true === $this->option('start')) {
            $this->infoLine('The import routine has started. The process is not visible. Please wait.');
            Log::debug('Go for import!');

            // run it!
            $key       = sprintf('import.routine.%s', $provider);
            $className = config($key);
            if (null === $className || !class_exists($className)) {
                // @codeCoverageIgnoreStart
                $this->errorLine(sprintf('No routine for provider "%s"', $provider));

                return 1;
                // @codeCoverageIgnoreEnd
            }

            // keep repeating this call until job lands on "provider_finished"
            $valid = ['provider_finished'];
            $count = 0;
            while (!\in_array($importJob->status, $valid, true) && $count < 6) {
                Log::debug(sprintf('Now in loop #%d.', $count + 1));
                /** @var RoutineInterface $routine */
                $routine = app($className);
                $routine->setImportJob($importJob);
                try {
                    $routine->run();
                } catch (FireflyException|Exception $e) {
                    $message = 'The import routine crashed: ' . $e->getMessage();
                    Log::error($message);
                    Log::error($e->getTraceAsString());

                    // set job errored out:
                    $jobRepository->setStatus($importJob, 'error');
                    $this->errorLine($message);

                    return 1;
                }
                $count++;
            }
            if ('provider_finished' === $importJob->status) {
                $this->infoLine('Import has finished. Please wait for storage of data.');
                // set job to be storing data:
                $jobRepository->setStatus($importJob, 'storing_data');

                /** @var ImportArrayStorage $storage */
                $storage = app(ImportArrayStorage::class);
                $storage->setImportJob($importJob);

                try {
                    $storage->store();
                } catch (FireflyException|Exception $e) {
                    $message = 'The import routine crashed: ' . $e->getMessage();
                    Log::error($message);
                    Log::error($e->getTraceAsString());

                    // set job errored out:
                    $jobRepository->setStatus($importJob, 'error');
                    $this->errorLine($message);

                    return 1;
                }
                // set storage to be finished:
                $jobRepository->setStatus($importJob, 'storage_finished');
            }

            // give feedback:
            $this->infoLine('Job has finished.');
            if (null !== $importJob->tag) {
                $this->infoLine(sprintf('%d transaction(s) have been imported.', $importJob->tag->transactionJournals->count()));
                $this->infoLine(sprintf('You can find your transactions under tag "%s"', $importJob->tag->tag));
            }

            if (null === $importJob->tag) {
                $this->errorLine('No transactions have been imported :(.');
            }
            if (\count($importJob->errors) > 0) {
                $this->infoLine(sprintf('%d error(s) occurred:', \count($importJob->errors)));
                foreach ($importJob->errors as $err) {
                    $this->errorLine('- ' . $err);
                }
            }
        }
        // clear cache for user:
        app('preferences')->setForUser($user, 'lastActivity', microtime());

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
        $file          = (string)$this->argument('file');
        $configuration = (string)$this->argument('configuration');
        $cwd           = getcwd();
        $validTypes    = config('import.options.file.import_formats');
        $type          = strtolower($this->option('type'));
        $provider      = strtolower($this->option('provider'));
        $enabled       = (bool)config(sprintf('import.enabled.%s', $provider));

        if (false === $enabled) {
            $this->errorLine(sprintf('Provider "%s" is not enabled.', $provider));

            return false;
        }

        if ('file' === $provider && !\in_array($type, $validTypes, true)) {
            $this->errorLine(sprintf('Cannot import file of type "%s"', $type));

            return false;
        }

        if ('file' === $provider && !file_exists($file)) {
            $this->errorLine(sprintf('Firefly III cannot find file "%s" (working directory: "%s").', $file, $cwd));

            return false;
        }

        if ('file' === $provider && !file_exists($configuration)) {
            $this->errorLine(sprintf('Firefly III cannot find configuration file "%s" (working directory: "%s").', $configuration, $cwd));

            return false;
        }

        return true;
    }
}
