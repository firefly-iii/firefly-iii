<?php declare(strict_types=1);


/**
 * ConfigSeeder.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\Configuration;
use Illuminate\Database\Seeder;

/**
 * Class ConfigSeeder
 */
class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entry = Configuration::where('name', 'db_version')->first();
        if (null === $entry) {
            Log::warning('No database version entry is present. Database is assumed to be OLD (version 1).');
            // FF old or no version present. Put at 1:
            Configuration::create(
                [
                    'name' => 'db_version',
                    'data' => 1,
                ]
            );
        }
        if (null !== $entry) {
            $version     = (int)config('firefly.db_version');
            $entry->data = $version;
            $entry->save();

            Log::warning(sprintf('Database entry exists. Update to latest version (%d)', $version));
        }
    }
}
