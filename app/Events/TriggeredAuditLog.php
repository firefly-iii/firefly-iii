<?php

/*
 * TriggeredAuditLog.php
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

namespace FireflyIII\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

/**
 * Class TriggeredAuditLog
 */
class TriggeredAuditLog extends Event
{
    use SerializesModels;

    public mixed  $after;
    public Model  $auditable;
    public mixed  $before;
    public Model  $changer;
    public string $field;

    /**
     * Create a new event instance.
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(Model $changer, Model $auditable, string $field, mixed $before, mixed $after)
    {
        $this->changer   = $changer;
        $this->auditable = $auditable;
        $this->field     = $field;
        $this->before    = $before;
        $this->after     = $after;
    }
}
