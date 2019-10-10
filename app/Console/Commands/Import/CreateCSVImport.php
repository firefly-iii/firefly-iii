<?php
/**
 * CreateCSVImport.php
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

/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Import;

use Exception;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;
use Log;

/**
 * Class CreateCSVImport.
 */
class CreateCSVImport extends Command
{
    use VerifiesAccessToken;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to create a new CSV file import.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature
        = 'firefly-iii:csv-import
                            {file? : The CSV file to import.}
                            {configuration? : The configuration file to use for the import.}
                            {--user=1 : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}';
    /** @var UserRepositoryInterface */
    private $userRepository;
    /** @var ImportJobRepositoryInterface */
    private $importRepository;
    /** @var ImportJob */
    private $importJob;

    /**
     * Run the command.
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        // @codeCoverageIgnoreStart
        if (!$this->verifyAccessToken()) {
            $this->errorLine('Invalid access token.');

            return 1;
        }

        if (!$this->validArguments()) {
            $this->errorLine('Invalid arguments.');

            return 1;
        }
        // @codeCoverageIgnoreEnd
        /** @var User $user */
        $user          = $this->userRepository->findNull((int)$this->option('user'));
        $file          = (string)$this->argument('file');
        $configuration = (string)$this->argument('configuration');

        $this->importRepository->setUser($user);

        $configurationData = json_decode(file_get_contents($configuration), true);
        $this->importJob   = $this->importRepository->create('file');


        // inform user (and log it)
        $this->infoLine(sprintf('Import file        : %s', $file));
        $this->infoLine(sprintf('Configuration file : %s', $configuration));
        $this->infoLine(sprintf('User               : #%d (%s)', $user->id, $user->email));
        $this->infoLine(sprintf('Job                : %s', $this->importJob->key));

        try {
            $this->storeFile($file);
        } catch (FireflyException $e) {
            $this->errorLine($e->getMessage());

            return 1;
        }

        // job is ready to go
        $this->importRepository->setConfiguration($this->importJob, $configurationData);
        $this->importRepository->setStatus($this->importJob, 'ready_to_run');

        $this->infoLine('The import routine has started. The process is not visible. Please wait.');
        Log::debug('Go for import!');


        // keep repeating this call until job lands on "provider_finished"
        try {
            $this->processFile();
        } catch (FireflyException $e) {
            $this->errorLine($e->getMessage());

            return 1;
        }

        // then store data:
        try {
            $this->storeData();
        } catch (FireflyException $e) {
            $this->errorLine($e->getMessage());

            return 1;
        }

        // give feedback:
        $this->giveFeedback();

        // clear cache for user:
        app('preferences')->setForUser($user, 'lastActivity', microtime());

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->userRepository   = app(UserRepositoryInterface::class);
        $this->importRepository = app(ImportJobRepositoryInterface::class);
    }

    /**
     * @param string $message
     * @param array|null $data
     * @codeCoverageIgnore
     */
    private function errorLine(string $message, array $data = null): void
    {
        Log::error($message, $data ?? []);
        $this->error($message);

    }

    /**
     * @param string $message
     * @param array $data
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     */
    private function validArguments(): bool
    {
        $file          = (string)$this->argument('file');
        $configuration = (string)$this->argument('configuration');
        $cwd           = getcwd();
        $enabled       = (bool)config('import.enabled.file');

        if (false === $enabled) {
            $this->errorLine('CSV Provider is not enabled.');

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

        $configurationData = json_decode(file_get_contents($configuration), true);
        if (null === $configurationData) {
            $this->errorLine(sprintf('Firefly III cannot read the contents of configuration file "%s" (working directory: "%s").', $configuration, $cwd));

            return false;
        }

        return true;
    }

    /**
     * Store the supplied file as an attachment to this job.
     *
     * @param string $file
     * @throws FireflyException
     */
    private function storeFile(string $file): void
    {
        // store file as attachment.
        if ('' !== $file) {
            $messages = $this->importRepository->storeCLIUpload($this->importJob, 'import_file', $file);
            if ($messages->count() > 0) {
                throw new FireflyException($messages->first());
            }
        }
    }

    /**
     * Keep repeating import call until job lands on "provider_finished".
     *
     * @throws FireflyException
     */
    private function processFile(): void
    {
        $className = config('import.routine.file');
        $valid     = ['provider_finished'];
        $count     = 0;

        while (!in_array($this->importJob->status, $valid, true) && $count < 6) {
            Log::debug(sprintf('Now in loop #%d.', $count + 1));
            /** @var RoutineInterface $routine */
            $routine = app($className);
            $routine->setImportJob($this->importJob);
            try {
                $routine->run();
            } catch (FireflyException|Exception $e) {
                $message = 'The import routine crashed: ' . $e->getMessage();
                Log::error($message);
                Log::error($e->getTraceAsString());

                // set job errored out:
                $this->importRepository->setStatus($this->importJob, 'error');
                throw new FireflyException($message);
            }
            $count++;
        }
        $this->importRepository->setStatus($this->importJob, 'provider_finished');
        $this->importJob->status = 'provider_finished';
    }

    /**
     *
     * @throws FireflyException
     */
    private function storeData(): void
    {
        if ('provider_finished' === $this->importJob->status) {
            $this->infoLine('Import has finished. Please wait for storage of data.');
            // set job to be storing data:
            $this->importRepository->setStatus($this->importJob, 'storing_data');

            /** @var ImportArrayStorage $storage */
            $storage = app(ImportArrayStorage::class);
            $storage->setImportJob($this->importJob);

            try {
                $storage->store();
            } catch (FireflyException|Exception $e) {
                $message = 'The import routine crashed: ' . $e->getMessage();
                Log::error($message);
                Log::error($e->getTraceAsString());

                // set job errored out:
                $this->importRepository->setStatus($this->importJob, 'error');
                throw new FireflyException($message);

            }
            // set storage to be finished:
            $this->importRepository->setStatus($this->importJob, 'storage_finished');
        }
    }

    /**
     *
     */
    private function giveFeedback(): void
    {
        $this->infoLine('Job has finished.');


        if (null !== $this->importJob->tag) {
            $this->infoLine(sprintf('%d transaction(s) have been imported.', $this->importJob->tag->transactionJournals->count()));
            $this->infoLine(sprintf('You can find your transactions under tag "%s"', $this->importJob->tag->tag));
        }

        if (null === $this->importJob->tag) {
            $this->errorLine('No transactions have been imported :(.');
        }
        if (count($this->importJob->errors) > 0) {
            $this->infoLine(sprintf('%d error(s) occurred:', count($this->importJob->errors)));
            foreach ($this->importJob->errors as $err) {
                $this->errorLine('- ' . $err);
            }
        }
    }
}
