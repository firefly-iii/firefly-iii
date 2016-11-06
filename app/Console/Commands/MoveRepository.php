<?php
/**
 * MoveRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class MoveRepository
 *
 * @package FireflyIII\Console\Commands
 */
class MoveRepository extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alerts the user that the Github repository will move, if they are interested to know this.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:github-move';

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
     */
    public function handle()
    {
        $moveDate = new Carbon('2017-01-01');
        $final    = new Carbon('2017-03-01');
        $now      = new Carbon;

        // display message before 2017-01-01
        if ($moveDate > $now) {
            $this->line('+------------------------------------------------------------------------------+');
            $this->line('');
            $this->line('The Github repository for Firefly III will MOVE');
            $this->line('This move will be on January 1st 2017');
            $this->line('');
            $this->error('READ THIS WIKI PAGE FOR MORE INFORMATION');
            $this->line('');
            $this->info('https://github.com/firefly-iii/help/wiki/New-Github-repository');
            $this->line('');
            $this->line('+------------------------------------------------------------------------------+');
        }

        // display message after 2017-01-01 but before 2017-03-01
        if ($moveDate <= $now && $now <= $final) {
            $this->line('+------------------------------------------------------------------------------+');
            $this->line('');
            $this->line('The Github repository for Firefly III has MOVED');
            $this->line('This move was on January 1st 2017!');
            $this->line('');
            $this->error('READ THIS WIKI PAGE FOR MORE INFORMATION');
            $this->line('');
            $this->info('https://github.com/firefly-iii/help/wiki/New-Github-repository');
            $this->line('');
            $this->line('+------------------------------------------------------------------------------+');
        }

    }
}
