<?php

/**
 * TelemetryRepository.php
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

namespace FireflyIII\Repositories\Telemetry;

use FireflyIII\Models\Telemetry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class TelemetryRepository
 */
class TelemetryRepository implements TelemetryRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return Telemetry::count();
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(): void
    {
        // created_at is never NULL.
        Telemetry::whereNotNull('created_at')->delete();
    }

    /**
     * @inheritDoc
     */
    public function deleteSubmitted(): void
    {
        // created_at is never NULL.
        Telemetry::whereNotNull('submitted')->delete();
    }

    /**
     * @inheritDoc
     */
    public function paginated(int $pageSize): LengthAwarePaginator
    {
        return Telemetry::orderBy('created_at', 'DESC')->paginate($pageSize);
    }
}
