<?php

/**
 * UpdateTrait.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Helpers\Update;

use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequestInterface;

/**
 * Trait UpdateTrait
 */
trait UpdateTrait
{
    /**
     * Returns an array with info on the next release, if any.
     * 'message' => 'A new version is available.
     * 'level' => 'info' / 'success' / 'error'
     */
    public function getLatestRelease(): array
    {
        app('log')->debug('Now in getLatestRelease()');

        /** @var UpdateRequestInterface $checker */
        $checker       = app(UpdateRequestInterface::class);
        $channelConfig = app('fireflyconfig')->get('update_channel', 'stable');
        $channel       = (string) $channelConfig->data;

        return $checker->getUpdateInformation($channel);
    }
}
