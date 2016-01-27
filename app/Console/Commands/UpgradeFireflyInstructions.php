<?php

namespace FireflyIII\Console\Commands;

use Config;
use Illuminate\Console\Command;

/**
 * Class UpgradeFireflyInstructions
 *
 * @package FireflyIII\Console\Commands
 */
class UpgradeFireflyInstructions extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:upgrade-instructions';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $version = Config::get('firefly.version');
        $config  = Config::get('upgrade.text');
        $text    = isset($config[$version]) ? $config[$version] : null;

        $this->line('+------------------------------------------------------------------------------+');
        $this->line('');

        if (is_null($text)) {
            $this->line('Thank you for installing Firefly III, v' . $version);
            $this->line('If you are upgrading from a previous version,');
            $this->info('there are no extra upgrade instructions.');
            $this->line('Firefly III should be ready for use.');
        } else {
            $this->line('Thank you for installing Firefly III, v' . $version);
            $this->line('If you are upgrading from a previous version,');
            $this->line('please follow these upgrade instructions carefully:');
            $this->info(wordwrap($text));
        }

        $this->line('');
        $this->line('+------------------------------------------------------------------------------+');
    }
}
