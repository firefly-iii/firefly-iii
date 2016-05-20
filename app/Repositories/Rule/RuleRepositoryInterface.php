<?php
/**
 * RuleRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Rule;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;

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
