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

        return $newRuleGroup;
    }

    /**
     * @param RuleGroup $ruleGroup
     * @param array     $data
     *
     * @return RuleGroup
     */
    public function update(RuleGroup $ruleGroup, array $data)
    {
        // update the account:
        $ruleGroup->title       = $data['title'];
        $ruleGroup->description = $data['description'];
        $ruleGroup->active      = $data['active'];
        $ruleGroup->save();

        return $ruleGroup;
    }
}