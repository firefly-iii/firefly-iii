<?php
/**
 * RuleGroupRepositoryInterface.php
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
     * Make sure rule group order is correct in DB.
     */
    public function correctRuleGroupOrder(): void;

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
     * Delete everything.
     */
    public function destroyAll(): void;

    /**
     * @param int $ruleGroupId
     *
     * @return RuleGroup|null
     */
    public function find(int $ruleGroupId): ?RuleGroup;

    /**
     * @param string $title
     *
     * @return RuleGroup|null
     */
    public function findByTitle(string $title): ?RuleGroup;

    /**
     * Get all rule groups.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @return Collection
     */
    public function getActiveGroups(): Collection;

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getActiveRules(RuleGroup $group): Collection;

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
     * @param string|null $filter
     *
     * @return Collection
     */
    public function getRuleGroupsWithRules(?string $filter): Collection;

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getRules(RuleGroup $group): Collection;

    /**
     * Get highest possible order for a rule group.
     *
     * @return int
     */
    public function maxOrder(): int;

    /**
     * @return bool
     */
    public function resetOrder(): bool;

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function resetRuleOrder(RuleGroup $ruleGroup): bool;

    /**
     * @param string $query
     * @param int    $limit
     *
     * @return Collection
     */
    public function searchRuleGroup(string $query, int $limit): Collection;

    /**
     * @param RuleGroup $ruleGroup
     * @param int       $newOrder
     */
    public function setOrder(RuleGroup $ruleGroup, int $newOrder): void;

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
