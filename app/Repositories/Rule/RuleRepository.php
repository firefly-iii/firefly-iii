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
}