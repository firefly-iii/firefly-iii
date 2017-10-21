<?php
/**
 * ExportJobRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\ExportJob;

use FireflyIII\Models\ExportJob;
use FireflyIII\User;

/**
 * Interface ExportJobRepositoryInterface
 *
 * @package FireflyIII\Repositories\ExportJob
 */
interface ExportJobRepositoryInterface
{
    /**
     * @param ExportJob $job
     * @param string    $status
     *
     * @return bool
     */
    public function changeStatus(ExportJob $job, string $status): bool;

    /**
     * @return bool
     */
    public function cleanup(): bool;

    /**
     * @return ExportJob
     */
    public function create(): ExportJob;

    /**
     * @param ExportJob $job
     *
     * @return bool
     */
    public function exists(ExportJob $job): bool;

    /**
     * @param string $key
     *
     * @return ExportJob
     */
    public function findByKey(string $key): ExportJob;

    /**
     * @param ExportJob $job
     *
     * @return string
     */
    public function getContent(ExportJob $job): string;

    /**
     * @param User $user
     */
    public function setUser(User $user);

}
