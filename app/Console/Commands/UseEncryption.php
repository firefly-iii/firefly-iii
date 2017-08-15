<?php
declare(strict_types=1);

/**
 * UseEncryption.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Class UseEncryption
 *
 * @package FireflyIII\Console\Commands
 */
class UseEncryption extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will make sure that entries in the database will be encrypted (or not) according to the settings in .env';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:use-encryption';

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
        $this->handleObjects('Account', 'name', 'encrypted');
        $this->handleObjects('Bill', 'name', 'name_encrypted');
        $this->handleObjects('Bill', 'match', 'match_encrypted');
        $this->handleObjects('Budget', 'name', 'encrypted');
        $this->handleObjects('Category', 'name', 'encrypted');
        $this->handleObjects('PiggyBank', 'name', 'encrypted');
        $this->handleObjects('TransactionJournal', 'description', 'encrypted');
    }

    /**
     * Run each object and encrypt them (or not).
     *
     * @param string $class
     * @param string $field
     * @param string $indicator
     */
    public function handleObjects(string $class, string $field, string $indicator)
    {
        $fqn     = sprintf('FireflyIII\Models\%s', $class);
        $encrypt = config('firefly.encryption') ? 0 : 1;
        $set     = $fqn::where($indicator, $encrypt)->get();

        foreach ($set as $entry) {
            $newName       = $entry->$field;
            $entry->$field = $newName;
            $entry->save();
        }

        $this->line(sprintf('Updated %d %s.', $set->count(), strtolower(Str::plural($class))));
    }
}
