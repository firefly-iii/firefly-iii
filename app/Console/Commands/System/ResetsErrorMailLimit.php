<?php

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ResetsErrorMailLimit extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:reset-error-mail-limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the number of error mails sent.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file      = storage_path('framework/cache/error-count.json');
        $directory = storage_path('framework/cache');
        $limits    = [];

        if (!is_writable($directory)) {
            $this->friendlyError(sprintf('Cannot write to directory "%s", cannot rate limit errors.', $directory));

            return CommandAlias::FAILURE;
        }
        if (!file_exists($file)) {
            $this->friendlyInfo(sprintf('Created new limits file at "%s"', $file));
            file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));
            return CommandAlias::SUCCESS;
        }
        if (!is_writable($file)) {
            $this->friendlyError(sprintf('Cannot write to "%s", cannot rate limit errors.', $file));

            return CommandAlias::FAILURE;
        }

        $this->friendlyInfo(sprintf('Successfully reset the error rate-limits file located at "%s"', $file));
        file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));

        return CommandAlias::SUCCESS;
    }
}
