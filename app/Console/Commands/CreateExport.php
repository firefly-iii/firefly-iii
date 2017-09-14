<?php
/**
 * CreateExport.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;


/**
 * Class CreateExportextends
 *
 * @package FireflyIII\Console\Commands
 */
class CreateExport extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used to create an export of your data. This will result in an UNENCRYPTED backup in your storage/export folder.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:create-export {--with_attachments} {--with_uploads}';

    /**
     * Create a new command instance.
     *
     * @return void
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
        //$user = User::find(1); // can ony
        //$first = '';
        //$today = new Carbon;
        //
        $this->error('Export is under construction.');
    }
}
