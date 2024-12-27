<?php

declare(strict_types=1);
/*
 * MigratePreferences.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Preference;
use FireflyIII\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CorrectsPreferences extends Command
{
    protected $description = 'Give Firefly III preferences a user group ID so they can be made administration specific.';

    protected $signature   = 'correction:preferences';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $items = config('firefly.admin_specific_prefs');
        $users = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            $count = 0;
            foreach ($items as $item) {
                $preference = Preference::where('name', $item)->where('user_id', $user->id)->first();
                if (null === $preference) {
                    continue;
                }
                if (null === $preference->user_group_id) {
                    $preference->user_group_id = $user->user_group_id;
                    $preference->save();
                    ++$count;
                }
            }
            if ($count > 0) {
                $this->info(sprintf('Migrated %d preference(s) for user #%d ("%s").', $count, $user->id, $user->email));
            }
        }

        return CommandAlias::SUCCESS;
    }
}
