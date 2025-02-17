<?php

/**
 * ExportData.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Export;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Export\ExportDataGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ExportsData extends Command
{
    use ShowsFriendlyMessages;
    use VerifiesAccessToken;

    protected $description = 'Command to export data from Firefly III.';

    protected $signature   = 'firefly-iii:export-data
    {--user=1 : The user ID that the export should run for.}
    {--token= : The user\'s access token.}
    {--start= : First transaction to export. Defaults to your very first transaction. Only applies to transaction export.}
    {--end= : Last transaction to export. Defaults to today. Only applies to transaction export.}
    {--accounts= : From which accounts or liabilities to export. Only applies to transaction export. Defaults to all of your asset accounts.}
    {--export_directory=./ : Where to store the export files.}
    {--export-transactions : Create a file with all your transactions and their meta data. This flag and the other flags can be combined.}
    {--export-accounts : Create a file with all your accounts and some meta data.}
    {--export-budgets : Create a file with all your budgets and some meta data.}
    {--export-categories : Create a file with all your categories and some meta data.}
    {--export-tags : Create a file with all your tags and some meta data.}
    {--export-recurring : Create a file with all your recurring transactions and some meta data.}
    {--export-rules : Create a file with all your rules and some meta data.}
    {--export-subscriptions : Create a file with all your subscriptions and some meta data.}
    {--export-piggies : Create a file with all your piggy banks and some meta data.}
    {--force : Force overwriting of previous exports if found.}';
    private AccountRepositoryInterface $accountRepository;
    private JournalRepositoryInterface $journalRepository;

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        // verify access token
        if (!$this->verifyAccessToken()) {
            $this->friendlyError('Invalid access token. Check /profile.');

            return 1;
        }
        // set up repositories.
        $this->stupidLaravel();
        $user       = $this->getUser();
        $this->journalRepository->setUser($user);
        $this->accountRepository->setUser($user);

        // get the options.
        try {
            $options = $this->parseOptions();
        } catch (FireflyException $e) {
            $this->friendlyError(sprintf('Could not work with your options: %s', $e));

            return 1;
        }

        // make export object and configure it.
        /** @var ExportDataGenerator $exporter */
        $exporter   = app(ExportDataGenerator::class);
        $exporter->setUser($user);

        $exporter->setStart($options['start']);
        $exporter->setEnd($options['end']);
        $exporter->setAccounts($options['accounts']);
        $exporter->setExportTransactions($options['export']['transactions']);
        $exporter->setExportAccounts($options['export']['accounts']);
        $exporter->setExportBudgets($options['export']['budgets']);
        $exporter->setExportCategories($options['export']['categories']);
        $exporter->setExportTags($options['export']['tags']);
        $exporter->setExportRecurring($options['export']['recurring']);
        $exporter->setExportRules($options['export']['rules']);
        $exporter->setExportBills($options['export']['bills']);
        $exporter->setExportPiggies($options['export']['piggies']);
        $data       = $exporter->export();
        if (0 === count($data)) {
            $this->friendlyError('You must export *something*. Use --export-transactions or another option. See docs.firefly-iii.org');
        }
        $returnCode = 0;
        if (0 !== count($data)) {
            try {
                $this->exportData($options, $data);
            } catch (FireflyException $e) {
                $this->friendlyError(sprintf('Could not store data: %s', $e->getMessage()));

                $returnCode = 1;
            }
        }

        return $returnCode;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->journalRepository = app(JournalRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    /**
     * @throws FireflyException
     * @throws \Exception
     */
    private function parseOptions(): array
    {
        $start    = $this->getDateParameter('start');
        $end      = $this->getDateParameter('end');
        $accounts = $this->getAccountsParameter();
        $export   = $this->getExportDirectory();

        return [
            'export'    => [
                'transactions' => $this->option('export-transactions'),
                'accounts'     => $this->option('export-accounts'),
                'budgets'      => $this->option('export-budgets'),
                'categories'   => $this->option('export-categories'),
                'tags'         => $this->option('export-tags'),
                'recurring'    => $this->option('export-recurring'),
                'rules'        => $this->option('export-rules'),
                'bills'        => $this->option('export-subscriptions'),
                'piggies'      => $this->option('export-piggies'),
            ],
            'start'     => $start,
            'end'       => $end,
            'accounts'  => $accounts,
            'directory' => $export,
            'force'     => $this->option('force'),
        ];
    }

    /**
     * @throws \Exception
     */
    private function getDateParameter(string $field): Carbon
    {
        $date  = today(config('app.timezone'))->subYear();
        $error = false;

        if (!in_array($field, ['start', 'end'], true)) {
            throw new FireflyException(sprintf('Invalid field "%s" given, can only be "start" or "end".', $field));
        }

        if (is_string($this->option($field))) {
            try {
                $date = Carbon::createFromFormat('!Y-m-d', $this->option($field));
            } catch (\InvalidArgumentException $e) {
                app('log')->error($e->getMessage());
                $this->friendlyError(sprintf('%s date "%s" must be formatted YYYY-MM-DD. Field will be ignored.', $field, $this->option('start')));
                $error = true;
            }
            if (null === $date) {
                $this->friendlyError(sprintf('%s date "%s" must be formatted YYYY-MM-DD.', $field, $this->option('start')));

                throw new FireflyException(sprintf('%s date "%s" must be formatted YYYY-MM-DD.', $field, $this->option('start')));
            }
        }
        if (null === $this->option($field)) {
            app('log')->info(sprintf('No date given in field "%s"', $field));
            $error = true;
        }

        if (true === $error && 'start' === $field) {
            $journal = $this->journalRepository->firstNull();
            $date    = null === $journal ? today(config('app.timezone'))->subYear() : $journal->date;
            $date->startOfDay();

            return $date;
        }
        // field can only be 'end' at this point, so no need to include it in the check.
        if (true === $error) {
            $date = today(config('app.timezone'));
            $date->endOfDay();

            return $date;
        }

        if ('end' === $field) {
            $date->endOfDay();
        }

        return $date;
    }

    /**
     * @throws FireflyException
     */
    private function getAccountsParameter(): Collection
    {
        $final       = new Collection();
        $accounts    = new Collection();
        $accountList = (string) $this->option('accounts');
        $types       = [AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
        if ('' !== $accountList) {
            $accountIds = explode(',', $accountList);
            $accounts   = $this->accountRepository->getAccountsById($accountIds);
        }
        if ('' === $accountList) {
            $accounts = $this->accountRepository->getAccountsByType($types);
        }

        // filter accounts,
        /** @var Account $account */
        foreach ($accounts as $account) {
            if (in_array($account->accountType->type, $types, true)) {
                $final->push($account);
            }
        }
        if (0 === $final->count()) {
            throw new FireflyException('300007: Ended up with zero valid accounts to export from.');
        }

        return $final;
    }

    /**
     * @throws FireflyException
     */
    private function getExportDirectory(): string
    {
        $directory = (string) $this->option('export_directory');
        if ('' === $directory) {
            $directory = './';
        }
        if (!is_writable($directory)) {
            throw new FireflyException(sprintf('Directory "%s" isn\'t writeable.', $directory));
        }

        return $directory;
    }

    /**
     * @throws FireflyException
     */
    private function exportData(array $options, array $data): void
    {
        $date = date('Y_m_d');
        foreach ($data as $key => $content) {
            $file = sprintf('%s%s_%s.csv', $options['directory'], $date, $key);
            if (false === $options['force'] && file_exists($file)) {
                throw new FireflyException(sprintf('File "%s" exists already. Use --force to overwrite.', $file));
            }
            if (true === $options['force'] && file_exists($file)) {
                $this->friendlyWarning(sprintf('File "%s" exists already but will be replaced.', $file));
            }
            // continue to write to file.
            file_put_contents($file, $content);
            $this->friendlyPositive(sprintf('Wrote %s-export to file "%s".', $key, $file));
        }
    }
}
