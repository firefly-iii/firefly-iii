<?php

/*
 * LDAPEventHandler.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events;


use FireflyIII\User;
use LdapRecord\Laravel\Events\Import\Imported;
use Log;

/**
 * Class LDAPEventHandler
 */
class LDAPEventHandler
{

    /**
     * @param Imported $event
     */
    public function importedUser(Imported $event)
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        /** @var User $user */
        $user        = $event->eloquent;
        $alternative = User::where('email', $user->email)->where('id', '!=', $user->id)->first();
        if (null !== $alternative) {
            Log::debug(sprintf('User #%d is created but user #%d already exists.', $user->id, $alternative->id));
            $alternative->objectguid = $user->objectguid;
            $alternative->domain     = $user->domain;
            $alternative->save();
            $user->delete();
            auth()->logout();
        }
    }

}
