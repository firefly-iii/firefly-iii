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

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface RuleGroupRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 *
 */
interface RuleGroupRepositoryInterface
{
    /**
     * Make sure rule group order is correct in DB.
     */
    public function correctRuleGroupOrder(): void;

    public function count(): int;

    public function destroy(RuleGroup $ruleGroup, ?RuleGroup $moveTo): bool;

    /**
     * Delete everything.
     */
    public function destroyAll(): void;

    public function find(int $ruleGroupId): ?RuleGroup;

    public function findByTitle(string $title): ?RuleGroup;

    /**
     * Get all rule groups.
     */
    public function get(): Collection;

    public function getActiveGroups(): Collection;

    public function getActiveRules(RuleGroup $group): Collection;

    public function getActiveStoreRules(RuleGroup $group): Collection;

    public function getActiveUpdateRules(RuleGroup $group): Collection;

    /**
     * Also inactive groups.
     */
    public function getAllRuleGroupsWithRules(?string $filter): Collection;

    public function getHighestOrderRuleGroup(): int;

    public function getRuleGroupsWithRules(?string $filter): Collection;

    public function getRules(RuleGroup $group): Collection;

    /**
     * Get highest possible order for a rule group.
     */
    public function maxOrder(): int;

    public function resetOrder(): bool;

    public function resetRuleOrder(RuleGroup $ruleGroup): bool;

    public function searchRuleGroup(string $query, int $limit): Collection;

    public function setOrder(RuleGroup $ruleGroup, int $newOrder): void;

    public function store(array $data): RuleGroup;

    public function update(RuleGroup $ruleGroup, array $data): RuleGroup;
}
