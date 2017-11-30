<?php
/**
 * RuleGroupRepositoryInterface.php
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

namespace FireflyIII\Repositories\RuleGroup;

use FireflyIII\Models\RuleGroup;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface RuleGroupRepositoryInterface.
 */
interface RuleGroupRepositoryInterface
{
    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param RuleGroup      $ruleGroup
     * @param RuleGroup|null $moveTo
     *
     * @return bool
     */
    public function destroy(RuleGroup $ruleGroup, ?RuleGroup $moveTo): bool;

    /**
     * @param int $ruleGroupId
     *
     * @return RuleGroup
     */
    public function find(int $ruleGroupId): RuleGroup;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param User $user
     *
     * @return Collection
     */
    public function getActiveGroups(User $user): Collection;

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getActiveStoreRules(RuleGroup $group): Collection;

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getActiveUpdateRules(RuleGroup $group): Collection;

    /**
     * @return int
     */
    public function getHighestOrderRuleGroup(): int;

    /**
     * @param User $user
     *
     * @return Collection
     */
    public function getRuleGroupsWithRules(User $user): Collection;

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveDown(RuleGroup $ruleGroup): bool;

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveUp(RuleGroup $ruleGroup): bool;

    /**
     * @return bool
     */
    public function resetRuleGroupOrder(): bool;

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function resetRulesInGroupOrder(RuleGroup $ruleGroup): bool;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return RuleGroup
     */
    public function store(array $data): RuleGroup;

    /**
     * @param RuleGroup $ruleGroup
     * @param array     $data
     *
     * @return RuleGroup
     */
    public function update(RuleGroup $ruleGroup, array $data): RuleGroup;
}
