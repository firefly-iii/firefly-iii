<?php




declare(strict_types=1);



namespace FireflyIII\Console\Commands\Tools;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequestInterface;
use FireflyIII\Support\Facades\AppConfiguration;
use Illuminate\Console\Command;

class ChecksForUpdates extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'firefly-iii:check-for-updates {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for Firefly III updates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $build      = Carbon::createFromTimestamp(config('firefly.build_time'), config('app.timezone'));
        $version    = config('firefly.version');

        $this->friendlyLine(sprintf('You are running version "%s", built on %s', $version, $build->format('Y-m-d H:i')));
        $permission = AppConfiguration::get('permission_update_check', -1)->data;
        if (1 !== $permission && false === $this->option('force')) {
            $this->friendlyWarning('Checking for updates is disabled. To overrule, use --force.');

            return Command::SUCCESS;
        }
        if (str_contains(config('firefly.version'), 'develop')) {
            $this->friendlyWarning('You are running a development version.');
        }

        /** @var UpdateRequestInterface $request */
        $request    = app(UpdateRequestInterface::class);
        // stable, alpha or beta
        $info       = $request->getUpdateInformation($version, $build, 'stable');

        if ('' !== $info->getError()) {
            $this->friendlyError($info->getError());

            return Command::FAILURE;
        }
        if (!$info->isNewVersionAvailable()) {
            $this->friendlyInfo(trans('firefly.no_new_release_available'));

            return Command::SUCCESS;
        }
        // if running develop, slightly different message.
        if (str_contains($version, 'develop')) {
            $this->friendlyInfo(trans('firefly.update_current_dev_older', ['version' => $version, 'new_version' => $info->getNewVersion()]));

            return Command::SUCCESS;
        }
        $this->friendlyInfo(trans('firefly.update_new_version_alert', [
            'your_version' => $version,
            'new_version'  => $info->getNewVersion(),
            'date'         => $info->getPublishedAt()->format('Y-m-d H:i:s'),
        ]));

        return Command::SUCCESS;
    }
}
