<?php

/*
 * RuleEngineInterface.php
 * Copyright (c) 2021 james@firefly-iii.org
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
/*
 * RuleEngineInterface.php
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

namespace FireflyIII\TransactionRules\Engine;

use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface RuleEngineInterface
 */
interface RuleEngineInterface
{
    /**
     * Add operators added to each search by the rule engine.
     *
     * @param array $operator
     */
    public function addOperator(array $operator): void;

    /**
     * Find all transactions only, dont apply anything.
     */
    public function find(): Collection;

    /**
     * Fire the rule engine.
     */
    public function fire(): void;

    /**
     * Return the number of changed transactions from the previous "fire" action.
     *
     * @return int
     */
    public function getResults(): int;

    /**
     * Add entire rule groups for the engine to execute.
     *
     * @param Collection $ruleGroups
     */
    public function setRuleGroups(Collection $ruleGroups): void;

    /**
     * Add rules for the engine to execute.
     *
     * @param Collection $rules
     */
    public function setRules(Collection $rules): void;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;

}
