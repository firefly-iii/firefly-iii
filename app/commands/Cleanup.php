<?php

use Illuminate\Console\Command;
//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;

/**
 * Class Cleanup
 */
class Cleanup extends Command
{

    /**
     * The console command description.
     *
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $description = 'Clean caches, regenerate some stuff.';
    // @codingStandardsIgnoreEnd
    /**
     * The console command name.
     *
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $name = 'firefly:cleanup';
    // @codingStandardsIgnoreEnd

    /**
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
    public function fire()
    {
        $this->info('Start!');
        Artisan::call('clear-compiled');
        $this->info('Cleared compiled...');
        Artisan::call('ide-helper:generate');
        $this->info('IDE helper, done...');
        Artisan::call('ide-helper:models', ['nowrite']);
        $this->info('IDE models, done...');
        Artisan::call('optimize');
        $this->info('Optimized...');
        Artisan::call('dump-autoload');
        $this->info('Done!');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
        //    ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
          //  ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

}
