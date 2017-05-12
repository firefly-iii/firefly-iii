<?php
/**
 * RuleGroupRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\RuleGroup;


use FireflyIII\Models\RuleGroup;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface RuleGroupRepositoryInterface
 *
 * @package FireflyIII\Repositories\RuleGroup
 */
interface RuleGroupRepositoryInterface
{

    /**
     *
     *
     * @return int
     */
    public function count(): int;

    /**
     * @param RuleGroup      $ruleGroup
     * @param RuleGroup|null $moveTo
     *
     * @return bool
     */
    public function destroy(RuleGroup $ruleGroup, RuleGroup $moveTo = null): bool;

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
