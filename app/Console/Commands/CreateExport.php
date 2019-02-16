<?php
/**
 * CreateExport.php
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

use Carbon\Carbon;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Class CreateExport.
 *
 * Generates export from the command line.
 *
 * @codeCoverageIgnore
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
     * Execute the console command.
     *
     * @return int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
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
        $user = $userRepository->findNull((int)$this->option('user'));
        if (null === $user) {
            return 1;
        }
        $jobRepository->setUser($user);
        $journalRepository->setUser($user);
        $accountRepository->setUser($user);

        // first date
        $firstJournal = $journalRepository->firstNull();
        $first        = new Carbon;
        if (null !== $firstJournal) {
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
        $disk      = Storage::disk('export');
        $fileName  = sprintf('export-%s.zip', date('Y-m-d_H-i-s'));
        $localPath = storage_path('export') . '/' . $job->key . '.zip';

        // "move" from local to export disk
        $disk->put($fileName, file_get_contents($localPath));
        unlink($localPath);

        $this->line('The export has finished! You can find the ZIP file in export disk with file name:');
        $this->line($fileName);

        return 0;
    }
}
