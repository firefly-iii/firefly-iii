<?php

/*
 * AuditEventHandler.php
 * Copyright (c) 2022 james@firefly-iii.org
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

use Carbon\Carbon;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Repositories\AuditLogEntry\ALERepositoryInterface;

/**
 * Class AuditEventHandler
 */
class AuditEventHandler
{
    public function storeAuditEvent(TriggeredAuditLog $event): void
    {
        $array = [
            'auditable' => $event->auditable,
            'changer'   => $event->changer,
            'action'    => $event->field,
            'before'    => $event->before,
            'after'     => $event->after,
        ];

        if ($event->before === $event->after) {
            app('log')->debug('Will not store event log because before and after are the same.');

            return;
        }
        if ($event->before instanceof Carbon && $event->after instanceof Carbon && $event->before->eq($event->after)) {
            app('log')->debug('Will not store event log because before and after Carbon values are the same.');

            return;
        }
        if ($event->before instanceof Carbon && $event->after instanceof Carbon) {
            $array['before'] = $event->before->toIso8601String();
            $array['after']  = $event->after->toIso8601String();
            app('log')->debug(sprintf('Converted "before" to "%s".', $event->before));
            app('log')->debug(sprintf('Converted "after" to "%s".', $event->after));
        }

        /** @var ALERepositoryInterface $repository */
        $repository = app(ALERepositoryInterface::class);
        $repository->store($array);
    }
}
