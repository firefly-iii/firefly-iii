<?php
/**
 * RuleRepository.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Repositories\Rule;

use Auth;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use Log;

/**
 * Class RuleRepository
 *
 * @package FireflyIII\Repositories\Rule
 */
class RuleRepository implements RuleRepositoryInterface
{
    /**
     * @return int
     */
    public function getHighestOrderRuleGroup()
    {
        $entry = Auth::user()->ruleGroups()->max('order');

        return intval($entry);
    }

    /**
     * @param array $data
     *
     * @return RuleGroup
     */
    public function storeRuleGroup(array $data)
    {
        $order = $this->getHighestOrderRuleGroup();

        $newRuleGroup = new RuleGroup(
            [
                'user_id'     => $data['user'],
                'title'       => $data['title'],
                'description' => $data['description'],
                'order'       => ($order + 1),
                'active'      => 1,


            ]
        );
        $newRuleGroup->save();
        $this->resetRuleGroupOrder();

        return $newRuleGroup;
    }

    /**
     * @param RuleGroup $ruleGroup
     * @param array     $data
     *
     * @return RuleGroup
     */
    public function updateRuleGroup(RuleGroup $ruleGroup, array $data)
    {
        // update the account:
        $ruleGroup->title       = $data['title'];
        $ruleGroup->description = $data['description'];
        $ruleGroup->active      = $data['active'];
        $ruleGroup->save();
        $this->resetRuleGroupOrder();

        return $ruleGroup;
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return boolean
     */
    public function destroyRuleGroup(RuleGroup $ruleGroup)
    {
        /** @var Rule $rule */
        foreach ($ruleGroup->rules as $rule) {
            $rule->delete();
        }

        $ruleGroup->delete();

        $this->resetRuleGroupOrder();

        return true;
    }

    /**
     * @return bool
     */
    public function resetRuleGroupOrder()
    {
        Auth::user()->ruleGroups()->whereNotNull('deleted_at')->update(['order' => 0]);

        $set   = Auth::user()->ruleGroups()->where('active', 1)->orderBy('order', 'ASC')->get();
        $count = 1;
        /** @var RuleGroup $entry */
        foreach ($set as $entry) {
            $entry->order = $count;
            $entry->save();
            $count++;
        }


        return true;
    }

    /**
     * @param Rule $rule
     * @return bool
     */
    public function moveRuleUp(Rule $rule)
    {
        $order = $rule->order;

        // find the rule with order-1 and give it order+1
        $other = $rule->ruleGroup->rules()->where('order', ($order - 1))->first();
        if ($other) {
            $other->order = ($other->order + 1);
            $other->save();
        }

        $rule->order = ($rule->order - 1);
        $rule->save();
        $this->resetRulesInGroupOrder($rule->ruleGroup);
    }

    /**
     * @param Rule $rule
     * @return bool
     */
    public function moveRuleDown(Rule $rule)
    {
        $order = $rule->order;

        // find the rule with order+1 and give it order-1
        $other = $rule->ruleGroup->rules()->where('order', ($order + 1))->first();
        if ($other) {
            $other->order = $other->order - 1;
            $other->save();
        }


        $rule->order = ($rule->order + 1);
        $rule->save();
        $this->resetRulesInGroupOrder($rule->ruleGroup);
    }

    /**
     * @return bool
     */
    public function resetRulesInGroupOrder(RuleGroup $ruleGroup)
    {
        $ruleGroup->rules()->whereNotNull('deleted_at')->update(['order' => 0]);

        $set   = $ruleGroup->rules()
                           ->orderBy('order', 'ASC')
                           ->orderBy('updated_at', 'DESC')
                           ->get();
        $count = 1;
        /** @var Rule $entry */
        foreach ($set as $entry) {
            $entry->order = $count;
            $entry->save();
            $count++;
        }

    }

    /**
     * @param RuleGroup $ruleGroup
     * @return bool
     */
    public function moveRuleGroupUp(RuleGroup $ruleGroup)
    {
        $order = $ruleGroup->order;

        // find the rule with order-1 and give it order+1
        $other = Auth::user()->ruleGroups()->where('order', ($order - 1))->first();
        if ($other) {
            $other->order = ($other->order + 1);
            $other->save();
        }

        $ruleGroup->order = ($ruleGroup->order - 1);
        $ruleGroup->save();
        $this->resetRuleGroupOrder();
    }

    /**
     * @param RuleGroup $ruleGroup
     * @return bool
     */
    public function moveRuleGroupDown(RuleGroup $ruleGroup)
    {
        $order = $ruleGroup->order;

        // find the rule with order+1 and give it order-1
        $other = Auth::user()->ruleGroups()->where('order', ($order + 1))->first();
        if ($other) {
            $other->order = ($other->order - 1);
            $other->save();
        }

        $ruleGroup->order = ($ruleGroup->order + 1);
        $ruleGroup->save();
        $this->resetRuleGroupOrder();
    }
}