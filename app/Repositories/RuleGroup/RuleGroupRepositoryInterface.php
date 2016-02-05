<?php
declare(strict_types = 1);

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
     * @return int
     */
    public function count();

    /**
     * @param RuleGroup $ruleGroup
     * @param RuleGroup $moveTo
     *
     * @return bool
     */
    public function destroy(RuleGroup $ruleGroup, RuleGroup $moveTo = null);

    /**
     * @return Collection
     */
    public function get();

    /**
     * @return int
     */
    public function getHighestOrderRuleGroup();

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveDown(RuleGroup $ruleGroup);

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveUp(RuleGroup $ruleGroup);

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
