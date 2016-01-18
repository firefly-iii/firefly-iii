<?php

namespace FireflyIII\Repositories\RuleGroup;


use FireflyIII\Models\RuleGroup;
use Illuminate\Support\Collection;

/**
 * Interface RuleGroupRepositoryInterface
 *
 * @package FireflyIII\Repositories\RuleGroup
 */
interface RuleGroupRepositoryInterface
{
    /**
     * @param RuleGroup $ruleGroup
     * @param RuleGroup $moveTo
     *
     * @return bool
     */
    public function destroy(RuleGroup $ruleGroup, RuleGroup $moveTo = null);


    /**
     * @return int
     */
    public function getHighestOrderRuleGroup();

    /**
     * @return Collection
     */
    public function get();

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveUp(RuleGroup $ruleGroup);

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveDown(RuleGroup $ruleGroup);

    /**
     * @return bool
     */
    public function resetRuleGroupOrder();

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function resetRulesInGroupOrder(RuleGroup $ruleGroup);

    /**
     * @param array $data
     *
     * @return RuleGroup
     */
    public function store(array $data);

    /**
     * @param RuleGroup $ruleGroup
     * @param array     $data
     *
     * @return RuleGroup
     */
    public function update(RuleGroup $ruleGroup, array $data);


}