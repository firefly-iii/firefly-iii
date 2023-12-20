<?php

/*
 * ShowsFriendlyMessages.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands;

/**
 * Trait ShowsFriendlyMessages
 */
trait ShowsFriendlyMessages
{
    public function friendlyError(string $message): void
    {
        $this->error(sprintf('  [x]  %s', trim($message)));
    }

    public function friendlyInfo(string $message): void
    {
        $this->friendlyNeutral($message);
    }

    public function friendlyNeutral(string $message): void
    {
        $this->line(sprintf('  [i] %s', trim($message)));
    }

    public function friendlyLine(string $message): void
    {
        $this->line(sprintf('      %s', trim($message)));
    }

    public function friendlyPositive(string $message): void
    {
        $this->info(sprintf('  [✓] %s', trim($message)));
    }

    public function friendlyWarning(string $message): void
    {
        $this->warn(sprintf('  [!] %s', trim($message)));
    }
}
