<?php

/**
 * ReportGeneratorInterface.php
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

namespace FireflyIII\Generator\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface ReportGeneratorInterface.
 */
interface ReportGeneratorInterface
{
    /**
     * Generate the report.
     */
    public function generate(): string;

    /**
     * Set the involved accounts.
     */
    public function setAccounts(Collection $accounts): self;

    /**
     * Set the involved budgets.
     */
    public function setBudgets(Collection $budgets): self;

    /**
     * Set the involved categories.
     */
    public function setCategories(Collection $categories): self;

    /**
     * Set the end date.
     */
    public function setEndDate(Carbon $date): self;

    /**
     * Set the expense accounts.
     */
    public function setExpense(Collection $expense): self;

    /**
     * Set the start date.
     */
    public function setStartDate(Carbon $date): self;

    /**
     * Set the tags.
     */
    public function setTags(Collection $tags): self;
}
