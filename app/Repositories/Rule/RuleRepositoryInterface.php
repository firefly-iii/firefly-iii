<?php

/**
 * RuleRepositoryInterface.php
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

namespace FireflyIII\Repositories\Rule;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface RuleRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface RuleRepositoryInterface
{
    public function count(): int;

    public function destroy(Rule $rule): bool;

    public function duplicate(Rule $rule): Rule;

    public function find(int $ruleId): ?Rule;

    /**
     * Get all the users rules.
     */
    public function getAll(): Collection;

    public function getFirstRuleGroup(): RuleGroup;

    public function getHighestOrderInRuleGroup(RuleGroup $ruleGroup): int;

    public function getPrimaryTrigger(Rule $rule): string;

    public function getRuleActions(Rule $rule): Collection;

    public function getRuleTriggers(Rule $rule): Collection;

    /**
     * Return search query for rule.
     */
    public function getSearchQuery(Rule $rule): string;

    /**
     * Get all the users rules that trigger on storage.
     */
    public function getStoreRules(): Collection;

    /**
     * Get all the users rules that trigger on update.
     */
    public function getUpdateRules(): Collection;

    public function maxOrder(RuleGroup $ruleGroup): int;

    public function moveRule(Rule $rule, RuleGroup $ruleGroup, int $order): Rule;

    public function resetRuleOrder(RuleGroup $ruleGroup): bool;

    public function searchRule(string $query, int $limit): Collection;

    public function setOrder(Rule $rule, int $newOrder): void;

    public function store(array $data): Rule;

    public function storeAction(Rule $rule, array $values): RuleAction;

    public function storeTrigger(Rule $rule, array $values): RuleTrigger;

    public function update(Rule $rule, array $data): Rule;
}
