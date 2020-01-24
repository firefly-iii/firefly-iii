<?php

/**
 * DecryptDatabase.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Crypt;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Preference;
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
    protected $signature = 'firefly-iii:decrypt-all';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
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

                    // A separate routine for preferences:
                    if ('preferences' === $table) {
                        // try to json_decrypt the value.
                        $value = json_decode($value, true) ?? $value;
                        Log::debug(sprintf('Decrypted field "%s" "%s" to "%s" in table "%s" (row #%d)', $field, $original, print_r($value, true), $table, $id));

                        /** @var Preference $object */
                        $object = Preference::find((int)$id);
                        if (null !== $object) {
                            $object->data = $value;
                            $object->save();
                        }
                        continue;
                    }

                    if ($value !== $original) {
                        Log::debug(sprintf('Decrypted field "%s" "%s" to "%s" in table "%s" (row #%d)', $field, $original, $value, $table, $id));
                        DB::table($table)->where('id', $id)->update([$field => $value]);
                    }
                }
            }
            $this->line(sprintf('Decrypted the data in table "%s".', $table));
            // mark as decrypted:
            $configName = sprintf('is_decrypted_%s', $table);
            app('fireflyconfig')->set($configName, true);

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
        $configVar  = app('fireflyconfig')->get($configName, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }


    /**
     * Tries to decrypt data. Will only throw an exception when the MAC is invalid.
     *
     * @param $value
     * @return string
     * @throws FireflyException
     */
    private function tryDecrypt($value)
    {
        try {
            $value = Crypt::decrypt($value); // verified
        } catch (DecryptException $e) {
            if ('The MAC is invalid.' === $e->getMessage()) {
                throw new FireflyException($e->getMessage()); // @codeCoverageIgnore
            }
            Log::debug(sprintf('Could not decrypt. %s', $e->getMessage()));
        }

        return $value;
    }
}
