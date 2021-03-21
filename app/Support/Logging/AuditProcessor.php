<?php

/**
 * AuditProcessor.php
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

namespace FireflyIII\Support\Logging;

/**
 * Class AuditProcessor
 *
 * @codeCoverageIgnore
 */
class AuditProcessor
{
    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array
    {
        if (auth()->check()) {

            $record['message'] = sprintf(
                'AUDIT: %s (%s (%s) -> %s:%s)',
                $record['message'],
                app('request')->ip(),
                auth()->user()->email,
                request()->method(), request()->url()
            );

            return $record;
        }

        $record['message'] = sprintf(
            'AUDIT: %s (%s -> %s:%s)',
            $record['message'],
            app('request')->ip(),
            request()->method(), request()->url()
        );

        return $record;
    }
}
