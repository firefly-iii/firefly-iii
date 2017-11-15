<?php
/**
 * RuleRepositoryInterface.php
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

namespace FireflyIII\Repositories\Rule;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\User;

/**
 * Interface RuleRepositoryInterface
 *
 * @package FireflyIII\Repositories\Rule
 */
interface RuleRepositoryInterface
{
    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    public function destroy(Rule $rule): bool;

    /**
     * @param int $ruleId
     *
     * @return Rule
     */
    public function find(int $ruleId): Rule;


    /**
     * @return RuleGroup
     */
    public function getFirstRuleGroup(): RuleGroup;

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return int
     */
    public function getHighestOrderInRuleGroup(RuleGroup $ruleGroup): int;

    /**
     * @param Rule $rule
     *
     * @return string
     */
    public function getPrimaryTrigger(Rule $rule): string;

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    public function moveDown(Rule $rule): bool;

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    public function moveUp(Rule $rule): bool;

    /**
     * @param Rule  $rule
     * @param array $ids
     *
     * @return bool
     */
    public function reorderRuleActions(Rule $rule, array $ids): bool;

    /**
     * @param Rule  $rule
     * @param array $ids
     *
     * @return bool
     */
    public function reorderRuleTriggers(Rule $rule, array $ids): bool;

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
     * @return Rule
     */
    public function store(array $data): Rule;

    /**
     * @param Rule  $rule
     * @param array $values
     *
     * @return RuleAction
     */
    public function storeAction(Rule $rule, array $values): RuleAction;

    /**
     * @param Rule  $rule
     * @param array $values
     *
     * @return RuleTrigger
     */
    public function storeTrigger(Rule $rule, array $values): RuleTrigger;

    /**
     * @param Rule  $rule
     * @param array $data
     *
     * @return Rule
     */
    public function update(Rule $rule, array $data): Rule;
}
