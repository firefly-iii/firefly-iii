<?php

namespace FireflyIII\Repositories\RuleGroup;


use FireflyIII\Models\RuleGroup;
use Illuminate\Support\Collection;

interface RuleGroupRepositoryInterface
{
    /**
     * @param RuleGroup $ruleGroup
     * @param RuleGroup $moveTo
     *
     * @return bool
     */
    public function destroyRuleGroup(RuleGroup $ruleGroup, RuleGroup $moveTo = null);



    /**
     * @return int
     */
    public function getHighestOrderRuleGroup();

    /**
     * @return Collection
     */
    public function getRuleGroups();

    /**
     * @param RuleGroup $ruleGroup
     * @return bool
     */
    public function moveRuleGroupUp(RuleGroup $ruleGroup);

    /**
     * @param RuleGroup $ruleGroup
     * @return bool
     */
    public function moveRuleGroupDown(RuleGroup $ruleGroup);

    /**
     * @return bool
     */
    public function resetRuleGroupOrder();

    /**
     * @return bool
     */
    public function resetRulesInGroupOrder(RuleGroup $ruleGroup);

    /**
     * @param array $data
     *
     * @return RuleGroup
     */
    public function storeRuleGroup(array $data);


    /**
     * @param RuleGroup $ruleGroup
     * @param array     $data
     *
     * @return RuleGroup
     */
    public function updateRuleGroup(RuleGroup $ruleGroup, array $data);


}