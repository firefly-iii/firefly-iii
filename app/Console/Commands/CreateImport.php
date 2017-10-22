<?php
/**
 * CreateImport.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Artisan;
use FireflyIII\Import\Logging\CommandHandler;
use FireflyIII\Import\Routine\ImportRoutine;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\MessageBag;
use Log;
use Monolog\Formatter\LineFormatter;

/**
 * Class CreateImport
 *
 * @package FireflyIII\Console\Commands
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
                            {configuration : The configuration file to use for the import/}
                            {--type=csv : The file type of the import.}
                            {--user= : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}
                            {--start : Starts the job immediately.}';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run the command.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // cannot be helped
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five exactly.
     */
    public function handle()
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return;
        }
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);
        $file           = $this->argument('file');
        $configuration  = $this->argument('configuration');
        $user           = $userRepository->find(intval($this->option('user')));
        $cwd            = getcwd();
        $type           = strtolower($this->option('type'));

        if (!$this->validArguments()) {
            return;
        }

        $configurationData = json_decode(file_get_contents($configuration));
        if (is_null($configurationData)) {
            $this->error(sprintf('Firefly III cannot read the contents of configuration file "%s" (working directory: "%s").', $configuration, $cwd));

            return;
        }

        $this->line(sprintf('Going to create a job to import file: %s', $file));
        $this->line(sprintf('Using configuration file: %s', $configuration));
        $this->line(sprintf('Import into user: #%d (%s)', $user->id, $user->email));
        $this->line(sprintf('Type of import: %s', $type));


        /** @var ImportJobRepositoryInterface $jobRepository */
        $jobRepository = app(ImportJobRepositoryInterface::class);
        $jobRepository->setUser($user);
        $job = $jobRepository->create($type);
        $this->line(sprintf('Created job "%s"', $job->key));


        Artisan::call('firefly:encrypt-file', ['file' => $file, 'key' => $job->key]);
        $this->line('Stored import data...');


        $job->configuration = $configurationData;
        $job->status        = 'configured';
        $job->save();
        $this->line('Stored configuration...');


        if ($this->option('start') === true) {
            $this->line('The import will start in a moment. This process is not visible...');
            Log::debug('Go for import!');

            // normally would refer to other firefly:start-import but that doesn't seem to work all to well...
            $monolog   = Log::getMonolog();
            $handler   = new CommandHandler($this);
            $formatter = new LineFormatter(null, null, false, true);
            $handler->setFormatter($formatter);
            $monolog->pushHandler($handler);


            // start the actual routine:
            /** @var ImportRoutine $routine */
            $routine = app(ImportRoutine::class);
            $routine->setJob($job);
            $routine->run();

            // give feedback.
            /** @var MessageBag $error */
            foreach ($routine->errors as $index => $error) {
                $this->error(sprintf('Error importing line #%d: %s', $index, $error));
            }
            $this->line(
                sprintf('The import has finished. %d transactions have been imported out of %d records.', $routine->journals->count(), $routine->lines)
            );
        }

        return;
    }

    /**
     * Verify user inserts correct arguments.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five exactly.
     */
    private function validArguments(): bool
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);
        $file           = $this->argument('file');
        $configuration  = $this->argument('configuration');
        $user           = $userRepository->find(intval($this->option('user')));
        $cwd            = getcwd();
        $validTypes     = array_keys(config('firefly.import_formats'));
        $type           = strtolower($this->option('type'));
        if (is_null($user->id)) {
            $this->error(sprintf('There is no user with ID %d.', $this->option('user')));

            return false;
        }

        if (!in_array($type, $validTypes)) {
            $this->error(sprintf('Cannot import file of type "%s"', $type));

            return false;
        }

        if (!file_exists($file)) {
            $this->error(sprintf('Firefly III cannot find file "%s" (working directory: "%s").', $file, $cwd));

            return false;
        }

        if (!file_exists($configuration)) {
            $this->error(sprintf('Firefly III cannot find configuration file "%s" (working directory: "%s").', $configuration, $cwd));

            return false;
        }

        return true;
    }
}
