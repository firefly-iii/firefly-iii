<?php namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

/**
 * Class Inspire
 *
 * @package FireflyIII\Console\Commands
 */
class Inspire extends Command
{

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'inspire';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL . Inspiring::quote() . PHP_EOL);
    }

}
