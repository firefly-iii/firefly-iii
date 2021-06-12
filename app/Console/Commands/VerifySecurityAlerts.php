<?php

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use Log;

/**
 * Class VerifySecurityAlerts
 */
class VerifySecurityAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:verify-security-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify security alerts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // remove old advisory
        app('fireflyconfig')->delete('upgrade_security_message');
        app('fireflyconfig')->delete('upgrade_security_level');

        // check for security advisories.
        $version = config('firefly.version');
        $disk    = Storage::disk('resources');
        if (!$disk->has('alerts.json')) {
            Log::debug('No alerts.json file present.');
            return 0;
        }
        $content = $disk->get('alerts.json');
        $json    = json_decode($content, true, 10);

        /** @var array $array */
        foreach ($json as $array) {
            if ($version === $array['version'] && true === $array['advisory']) {
                Log::debug(sprintf('Version %s has an alert!', $array['version']));
                // add advisory to configuration.
                app('fireflyconfig')->set('upgrade_security_message', $array['message']);
                app('fireflyconfig')->set('upgrade_security_level', $array['level']);

                // depends on level
                if ('info' === $array['level']) {
                    Log::debug('INFO level alert');
                    $this->info($array['message']);
                    return 0;
                }
                if ('warning' === $array['level']) {
                    Log::debug('WARNING level alert');
                    $this->warn('------------------------ :o');
                    $this->warn($array['message']);
                    $this->warn('------------------------ :o');
                    return 0;
                }
                if ('danger' === $array['level']) {
                    Log::debug('DANGER level alert');
                    $this->error('------------------------ :-(');
                    $this->error($array['message']);
                    $this->error('------------------------ :-(');
                    return 0;
                }

                return 0;
            }
        }
        Log::debug('This version is not mentioned.');

        return 0;
    }
}
