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

use Monolog\LogRecord;

/**
 * Class AuditProcessor
 */
class AuditProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        if (auth()->check()) {
            $message = sprintf(
                'AUDIT: %s (%s (%s) -> %s:%s)',
                $record['message'], // @phpstan-ignore-line
                app('request')->ip(),
                auth()->user()->email,
                request()->method(),
                request()->url()
            );

            return new LogRecord($record->datetime, $record->channel, $record->level, $message, $record->context, $record->extra, $record->formatted);
        }

        $message = sprintf(
            'AUDIT: %s (%s -> %s:%s)',
            $record['message'], // @phpstan-ignore-line
            app('request')->ip(),
            request()->method(),
            request()->url()
        );

        return new LogRecord($record->datetime, $record->channel, $record->level, $message, $record->context, $record->extra, $record->formatted);
    }
}
