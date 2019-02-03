<?php

namespace FireflyIII\Console\Commands;

use Crypt;
use DB;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Log;

/**
 *
 * Class DecryptDatabase
 */
class DecryptDatabase extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypts the database.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:decrypt-all';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Going to decrypt the database.');
        $tables = [
            'accounts'             => ['name', 'iban'],
            'attachments'          => ['filename', 'mime', 'title', 'description'],
            'bills'                => ['name', 'match'],
            'budgets'              => ['name'],
            'categories'           => ['name'],
            'piggy_banks'          => ['name'],
            'preferences'          => ['data'],
            'tags'                 => ['tag', 'description'],
            'transaction_journals' => ['description'],
            'transactions'         => ['description'],
            'journal_links'        => ['comment'],
        ];

        foreach ($tables as $table => $fields) {
            if ($this->isDecrypted($table)) {
                $this->info(sprintf('No decryption required for table "%s".', $table));
                continue;
            }
            foreach ($fields as $field) {
                $rows = DB::table($table)->get(['id', $field]);
                foreach ($rows as $row) {
                    $original = $row->$field;
                    if (null === $original) {
                        continue;
                    }
                    $id    = $row->id;
                    $value = $this->tryDecrypt($original);
                    if ($value !== $original) {
                        Log::debug(sprintf('Decrypted field "%s" "%s" to "%s" in table "%s" (row #%d)', $field, $original, $value, $table, $id));
                        DB::table($table)->where('id', $id)->update([$field => $value]);
                    }
                }
            }
            $this->line(sprintf('Decrypted the data in table "%s".', $table));
            // mark as decrypted:
            $configName = sprintf('is_decrypted_%s', $table);
            FireflyConfig::set($configName, true);

        }
        $this->info('Done!');

        return 0;
    }

    /**
     * @param string $table
     *
     * @return bool
     */
    private function isDecrypted(string $table): bool
    {
        $configName = sprintf('is_decrypted_%s', $table);
        $configVar  = FireflyConfig::get($configName, false);
        if (null !== $configVar) {
            return $configVar->data;
        }

        return false;
    }


    /**
     * @param $value
     *
     * @return mixed
     */
    private function tryDecrypt($value)
    {
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            //Log::debug(sprintf('Could not decrypt. %s', $e->getMessage()));
        }

        return $value;
    }
}
