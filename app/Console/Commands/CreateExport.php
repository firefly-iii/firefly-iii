<?php
/**
 * CreateExport.php
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

use Carbon\Carbon;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Console\Command;
use Storage;


/**
 * Class CreateExport
 *
 * Generates export from the command line.
 *
 * @package FireflyIII\Console\Commands
 */
class CreateExport extends Command
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
        = 'firefly:create-export
                            {--user= : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}
                            {--with_attachments : Include user\'s attachments?}
                            {--with_uploads : Include user\'s uploads?}';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five its fine.
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return;
        }
        $this->line('Full export is running...');
        // make repositories
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);
        /** @var ExportJobRepositoryInterface $jobRepository */
        $jobRepository = app(ExportJobRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        /** @var JournalRepositoryInterface $journalRepository */
        $journalRepository = app(JournalRepositoryInterface::class);

        // set user
        $user = $userRepository->find(intval($this->option('user')));
        $jobRepository->setUser($user);
        $journalRepository->setUser($user);
        $accountRepository->setUser($user);

        // first date
        $firstJournal = $journalRepository->first();
        $first        = new Carbon;
        if (!is_null($firstJournal->id)) {
            $first = $firstJournal->date;
        }

        // create job and settings.
        $job      = $jobRepository->create();
        $settings = [
            'accounts'           => $accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]),
            'startDate'          => $first,
            'endDate'            => new Carbon,
            'exportFormat'       => 'csv',
            'includeAttachments' => $this->option('with_attachments'),
            'includeOldUploads'  => $this->option('with_uploads'),
            'job'                => $job,
        ];


        /** @var ProcessorInterface $processor */
        $processor = app(ProcessorInterface::class);
        $processor->setSettings($settings);

        $processor->collectJournals();
        $processor->convertJournals();
        $processor->exportJournals();
        if ($settings['includeAttachments']) {
            $processor->collectAttachments();
        }

        if ($settings['includeOldUploads']) {
            $processor->collectOldUploads();
        }

        $processor->createZipFile();
        $disk     = Storage::disk('export');
        $fileName = sprintf('export-%s.zip', date('Y-m-d_H-i-s'));
        $disk->move($job->key . '.zip', $fileName);

        $this->line('The export has finished! You can find the ZIP file in this location:');
        $this->line(storage_path(sprintf('export/%s', $fileName)));

        return;
    }
}
