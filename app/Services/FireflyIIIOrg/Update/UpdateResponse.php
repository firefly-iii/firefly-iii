<?php

declare(strict_types=1);

/*
 * UpdateResponse.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Services\FireflyIIIOrg\Update;

use Carbon\Carbon;

class UpdateResponse
{
    private bool   $newVersionAvailable = false;
    private string $error               = '';
    private string $newVersion          = '1.0.0';
    private Carbon $publishedAt;

    public function getError(): string
    {
        return $this->error;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }

    public function getPublishedAt(): Carbon
    {
        return $this->publishedAt;
    }

    public function isNewVersionAvailable(): bool
    {
        return $this->newVersionAvailable;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function setNewVersion(string $newVersion): void
    {
        $this->newVersion = $newVersion;
    }

    public function setNewVersionAvailable(bool $newVersionAvailable): void
    {
        $this->newVersionAvailable = $newVersionAvailable;
    }

    public function setPublishedAt(Carbon $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }
}
