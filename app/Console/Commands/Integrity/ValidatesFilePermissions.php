<?php




declare(strict_types=1);



namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidatesFilePermissions extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'integrity:file-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::debug(sprintf('Start of %s', $this->signature));
        $directories = [storage_path('upload')];
        $errors      = false;

        /** @var string $directory */
        foreach ($directories as $directory) {
            Log::debug(sprintf('Processing directory: %s', $directory));
            if (!is_dir($directory)) {
                $message = sprintf('Directory "%s" cannot found. It is necessary to allow files to be uploaded.', $directory);
                Log::error($message);
                $this->friendlyError($message);
                $errors  = true;

                continue;
            }
            Log::debug('It is a directory!');
            if (!is_writable($directory)) {
                $message = sprintf('Directory "%s" is not writeable. Uploading attachments may fail silently.', $directory);
                $this->friendlyError($message);
                Log::error($message);
                $errors  = true;
            }
            Log::debug('It is writeable!');
            Log::debug(sprintf('Done processing %s', $directory));
        }
        Log::debug('Done with loop.');
        if (false === $errors) {
            Log::debug('No errors.');
            $this->friendlyInfo('All necessary file paths seem to exist, and are writeable.');
        }

        Log::debug(sprintf('End of %s', $this->signature));

        return self::SUCCESS;
    }
}
