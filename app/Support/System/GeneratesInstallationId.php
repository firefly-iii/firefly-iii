<?php

/**
 * GeneratesInstallationId.php
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

namespace FireflyIII\Support\System;

use FireflyIII\Exceptions\FireflyException;
use Ramsey\Uuid\Uuid;

/**
 * Trait GeneratesInstallationId
 */
trait GeneratesInstallationId
{
    protected function generateInstallationId(): void
    {
        try {
            $config = app('fireflyconfig')->get('installation_id');
        } catch (FireflyException $e) {
            app('log')->info('Could not create or generate installation ID. Do not continue.');

            return;
        }

        // delete if wrong UUID:
        if (null !== $config && 'b2c27d92-be90-5c10-8589-005df5b314e6' === $config->data) {
            $config = null;
        }

        if (null === $config) {
            $uuid4    = Uuid::uuid4();
            $uniqueId = (string) $uuid4;
            app('log')->info(sprintf('Created Firefly III installation ID %s', $uniqueId));
            app('fireflyconfig')->set('installation_id', $uniqueId);
        }
    }
}
